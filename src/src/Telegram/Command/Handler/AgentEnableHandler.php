<?php

declare(strict_types=1);

namespace App\Telegram\Command\Handler;

use App\AgentRegistry\AgentRegistryInterface;
use App\Telegram\DTO\NormalizedEvent;
use App\Telegram\Service\TelegramSender;

final class AgentEnableHandler
{
    public function __construct(
        private readonly AgentRegistryInterface $agentRegistry,
    ) {
    }

    public function handle(NormalizedEvent $event, TelegramSender $sender, string $agentName, string $role): void
    {
        $agent = $this->agentRegistry->findByName($agentName);

        if (null === $agent) {
            $this->reply($event, $sender, sprintf(
                'Агент "%s" не знайдений. Використовуйте /agents для списку.',
                $agentName,
            ));

            return;
        }

        if ($agent['enabled'] ?? false) {
            $this->reply($event, $sender, sprintf('Агент %s вже увімкнений.', $agentName));

            return;
        }

        $enabledBy = $event->sender->username ?? $event->sender->id;
        $result = $this->agentRegistry->enable($agentName, $enabledBy);

        if ($result) {
            $this->reply($event, $sender, sprintf('Агент %s увімкнений.', $agentName));
        } else {
            $this->reply($event, $sender, sprintf('Не вдалося увімкнути агента %s.', $agentName));
        }
    }

    private function reply(NormalizedEvent $event, TelegramSender $sender, string $text): void
    {
        $options = [];
        if (null !== $event->chat->threadId) {
            $options['thread_id'] = $event->chat->threadId;
        }

        $sender->send($event->botId, $event->chat->id, $text, $options);
    }
}
