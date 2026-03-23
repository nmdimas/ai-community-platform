<?php

declare(strict_types=1);

namespace App\Telegram\Service;

use App\Telegram\DTO\NormalizedEvent;
use App\Telegram\Repository\TelegramChatRepository;
use Psr\Log\LoggerInterface;

final class TelegramChatTracker
{
    public function __construct(
        private readonly TelegramChatRepository $chatRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function track(NormalizedEvent $event, string $botDbId): void
    {
        $chatId = (int) $event->chat->id;
        if (0 === $chatId) {
            return;
        }

        $now = new \DateTimeImmutable();

        // Handle bot join/leave
        if ('member_joined' === $event->eventType && $event->sender->isBot) {
            $this->handleBotJoined($botDbId, $chatId, $event, $now);

            return;
        }

        if ('member_left' === $event->eventType && $event->sender->isBot) {
            $this->handleBotLeft($botDbId, $chatId, $now);

            return;
        }

        // Upsert chat on regular messages
        $this->ensureChatExists($botDbId, $chatId, $event);

        // Update activity timestamp
        $this->chatRepository->updateLastMessageTime($botDbId, $chatId, $now);
    }

    private function handleBotJoined(string $botDbId, int $chatId, NormalizedEvent $event, \DateTimeImmutable $now): void
    {
        $this->chatRepository->upsert([
            'bot_id' => $botDbId,
            'chat_id' => $chatId,
            'title' => $event->chat->title,
            'type' => $event->chat->type,
            'has_threads' => null !== $event->chat->threadId,
            'joined_at' => $now,
        ]);

        $this->chatRepository->markJoined($botDbId, $chatId, $now);

        $this->logger->info('Bot joined chat', [
            'bot_id' => $botDbId,
            'chat_id' => $chatId,
            'chat_title' => $event->chat->title,
        ]);
    }

    private function handleBotLeft(string $botDbId, int $chatId, \DateTimeImmutable $now): void
    {
        $this->chatRepository->markLeft($botDbId, $chatId, $now);

        $this->logger->info('Bot left chat', [
            'bot_id' => $botDbId,
            'chat_id' => $chatId,
        ]);
    }

    private function ensureChatExists(string $botDbId, int $chatId, NormalizedEvent $event): void
    {
        $existing = $this->chatRepository->findByBotAndChatId($botDbId, $chatId);

        if (!$existing) {
            $this->chatRepository->create([
                'bot_id' => $botDbId,
                'chat_id' => $chatId,
                'title' => $event->chat->title,
                'type' => $event->chat->type,
                'has_threads' => null !== $event->chat->threadId,
            ]);

            $this->logger->info('New chat tracked', [
                'bot_id' => $botDbId,
                'chat_id' => $chatId,
                'chat_title' => $event->chat->title,
            ]);

            return;
        }

        // Update title if changed
        if (null !== $event->chat->title && $event->chat->title !== ($existing['title'] ?? '')) {
            $this->chatRepository->update($existing['id'], [
                'title' => $event->chat->title,
            ]);
        }

        // Detect thread support
        if (null !== $event->chat->threadId && !($existing['has_threads'] ?? false)) {
            $this->chatRepository->update($existing['id'], [
                'has_threads' => true,
            ]);
        }
    }
}
