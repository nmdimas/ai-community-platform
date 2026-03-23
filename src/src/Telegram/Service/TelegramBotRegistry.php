<?php

declare(strict_types=1);

namespace App\Telegram\Service;

use App\Telegram\Repository\TelegramBotRepository;
use Psr\Log\LoggerInterface;

class TelegramBotRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $botsCache = [];
    private ?\DateTimeImmutable $cacheExpiry = null;
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBot(string $botId): ?array
    {
        // Check if we need to refresh the cache
        if ($this->shouldRefreshCache()) {
            $this->refreshCache();
        }

        // First check the cache
        if (isset($this->botsCache[$botId])) {
            return $this->botsCache[$botId];
        }

        // If not in cache, try to load from database
        $bot = $this->repository->findById($botId);
        if ($bot) {
            $this->botsCache[$botId] = $bot;
            $this->logger->debug('Loaded Telegram bot from database', [
                'bot_id' => $botId,
                'bot_username' => $bot['bot_username'],
            ]);
        } else {
            $this->logger->warning('Telegram bot not found', ['bot_id' => $botId]);
        }

        return $bot;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBotByUsername(string $username): ?array
    {
        // Check if we need to refresh the cache
        if ($this->shouldRefreshCache()) {
            $this->refreshCache();
        }

        // Search in cache first
        foreach ($this->botsCache as $bot) {
            if ($bot['bot_username'] === $username) {
                return $bot;
            }
        }

        // If not in cache, try to load from database
        $bot = $this->repository->findByUsername($username);
        if ($bot) {
            $this->botsCache[$bot['id']] = $bot;
            $this->logger->debug('Loaded Telegram bot by username from database', [
                'bot_username' => $username,
                'bot_id' => $bot['id'],
            ]);
        }

        return $bot;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getEnabledBots(): array
    {
        // Always fetch fresh data for this method
        $this->refreshCache();

        // Filter enabled bots from cache
        return array_filter($this->botsCache, fn ($bot) => true === $bot['enabled']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAllBots(): array
    {
        $this->refreshCache();

        return array_values($this->botsCache);
    }

    /**
     * @param array<string, mixed> $botData
     */
    public function registerBot(array $botData): string
    {
        // Validate required fields
        if (empty($botData['bot_username']) || empty($botData['bot_token'])) {
            throw new \InvalidArgumentException('Bot username and token are required');
        }

        // Check if bot already exists
        $existing = $this->repository->findByUsername($botData['bot_username']);
        if ($existing) {
            throw new \RuntimeException(sprintf('Bot with username "%s" already exists', $botData['bot_username']));
        }

        // Generate webhook secret if not provided
        if (empty($botData['webhook_secret'])) {
            $botData['webhook_secret'] = bin2hex(random_bytes(32));
        }

        // Create the bot
        $botId = $this->repository->create($botData);

        // Clear cache to force refresh
        $this->clearCache();

        $this->logger->info('Telegram bot registered', [
            'bot_id' => $botId,
            'bot_username' => $botData['bot_username'],
        ]);

        return $botId;
    }

    /**
     * @param array<string, mixed> $updates
     */
    public function updateBot(string $botId, array $updates): bool
    {
        $result = $this->repository->update($botId, $updates);

        if ($result) {
            // Clear cache to force refresh
            $this->clearCache();

            $this->logger->info('Telegram bot updated', [
                'bot_id' => $botId,
                'updates' => array_keys($updates),
            ]);
        }

        return $result;
    }

    public function removeBot(string $botId): bool
    {
        $bot = $this->getBot($botId);
        if (!$bot) {
            return false;
        }

        $result = $this->repository->delete($botId);

        if ($result) {
            // Clear cache
            $this->clearCache();

            $this->logger->info('Telegram bot removed', [
                'bot_id' => $botId,
                'bot_username' => $bot['bot_username'],
            ]);
        }

        return $result;
    }

    public function verifyWebhookSecret(string $botId, string $secret): bool
    {
        $bot = $this->getBot($botId);
        if (!$bot) {
            return false;
        }

        // If no webhook secret is configured, reject all webhooks for security
        if (empty($bot['webhook_secret'])) {
            $this->logger->warning('Webhook verification failed: no secret configured', [
                'bot_id' => $botId,
            ]);

            return false;
        }

        return hash_equals($bot['webhook_secret'], $secret);
    }

    public function updateLastUpdateId(string $botId, int $updateId): void
    {
        $this->repository->updateLastUpdateId($botId, $updateId);

        // Update cache if bot is cached
        if (isset($this->botsCache[$botId])) {
            $this->botsCache[$botId]['last_update_id'] = $updateId;
        }
    }

    private function shouldRefreshCache(): bool
    {
        return null === $this->cacheExpiry || $this->cacheExpiry < new \DateTimeImmutable();
    }

    private function refreshCache(): void
    {
        $bots = $this->repository->findEnabled();

        $this->botsCache = [];
        foreach ($bots as $bot) {
            $this->botsCache[$bot['id']] = $bot;
        }

        $this->cacheExpiry = (new \DateTimeImmutable())->modify('+'.self::CACHE_TTL_SECONDS.' seconds');

        $this->logger->debug('Telegram bot registry cache refreshed', [
            'bot_count' => count($this->botsCache),
            'cache_expiry' => $this->cacheExpiry->format('Y-m-d H:i:s'),
        ]);
    }

    private function clearCache(): void
    {
        $this->botsCache = [];
        $this->cacheExpiry = null;
    }
}
