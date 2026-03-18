<?php

declare(strict_types=1);

namespace App\Controller\Api\Webhook;

use App\Telegram\Command\TelegramCommandRouter;
use App\Telegram\EventBus\TelegramEventPublisher;
use App\Telegram\Service\TelegramBotRegistry;
use App\Telegram\Service\TelegramChatTracker;
use App\Telegram\Service\TelegramUpdateNormalizer;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TelegramWebhookController extends AbstractController
{
    public function __construct(
        private readonly TelegramBotRegistry $botRegistry,
        private readonly TelegramUpdateNormalizer $normalizer,
        private readonly TelegramChatTracker $chatTracker,
        private readonly TelegramEventPublisher $eventPublisher,
        private readonly TelegramCommandRouter $commandRouter,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/api/v1/webhook/telegram/{botId}', name: 'api_webhook_telegram', methods: ['POST'])]
    public function __invoke(Request $request, string $botId): JsonResponse
    {
        // Look up bot by ID
        $bot = $this->botRegistry->getBot($botId);
        if (!$bot) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        // Check if bot is enabled
        if (!($bot['enabled'] ?? false)) {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        // Verify webhook secret
        $secretHeader = $request->headers->get('X-Telegram-Bot-Api-Secret-Token', '');
        if (!$this->botRegistry->verifyWebhookSecret($botId, (string) $secretHeader)) {
            $this->logger->warning('Telegram webhook secret verification failed', [
                'bot_id' => $botId,
                'ip' => $request->getClientIp(),
            ]);

            return new JsonResponse(null, Response::HTTP_UNAUTHORIZED);
        }

        // Parse update
        $content = $request->getContent();

        try {
            /** @var array<string, mixed> $update */
            $update = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning('Invalid JSON in Telegram webhook', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        // Check update_id for deduplication
        $updateId = (int) ($update['update_id'] ?? 0);
        $lastUpdateId = (int) ($bot['last_update_id'] ?? 0);

        if ($updateId > 0 && $updateId <= $lastUpdateId) {
            $this->logger->debug('Duplicate Telegram update skipped', [
                'bot_id' => $botId,
                'update_id' => $updateId,
                'last_update_id' => $lastUpdateId,
            ]);

            return new JsonResponse(null, Response::HTTP_OK);
        }

        // Gap detection
        if ($lastUpdateId > 0 && $updateId > $lastUpdateId + 1) {
            $this->logger->warning('Telegram update_id gap detected', [
                'bot_id' => $botId,
                'expected' => $lastUpdateId + 1,
                'received' => $updateId,
                'gap' => $updateId - $lastUpdateId - 1,
            ]);
        }

        // Update last_update_id
        if ($updateId > 0) {
            $this->botRegistry->updateLastUpdateId($botId, $updateId);
        }

        $this->logger->info('Telegram webhook update received', [
            'bot_id' => $botId,
            'update_id' => $updateId,
            'channel' => 'telegram',
        ]);

        // Normalize and process
        $events = $this->normalizer->normalize($update, $botId);

        foreach ($events as $event) {
            try {
                // Track chat metadata
                $this->chatTracker->track($event, $botId);

                // Route commands to CommandRouter
                if ('command_received' === $event->eventType) {
                    $this->commandRouter->route($event);
                }

                // Publish to Event Bus (all events including commands go to agents)
                $this->eventPublisher->publish($event);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to process Telegram event', [
                    'bot_id' => $botId,
                    'event_type' => $event->eventType,
                    'error' => $e->getMessage(),
                    'trace_id' => $event->traceId,
                ]);
            }
        }

        // Always return 200 to Telegram to prevent retries
        return new JsonResponse(null, Response::HTTP_OK);
    }
}
