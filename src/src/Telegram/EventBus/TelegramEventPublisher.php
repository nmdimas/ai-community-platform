<?php

declare(strict_types=1);

namespace App\Telegram\EventBus;

use App\EventBus\EventBus;
use App\Telegram\DTO\NormalizedEvent;
use Psr\Log\LoggerInterface;

final class TelegramEventPublisher
{
    public function __construct(
        private readonly EventBus $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function publish(NormalizedEvent $event): void
    {
        $this->logger->info('Publishing Telegram event to EventBus', [
            'event_type' => $event->eventType,
            'bot_id' => $event->botId,
            'chat_id' => $event->chat->id,
            'trace_id' => $event->traceId,
        ]);

        $this->eventBus->dispatch($event->eventType, $event->toArray());
    }
}
