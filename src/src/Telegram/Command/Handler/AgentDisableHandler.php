<?php

declare(strict_types=1);

namespace App\Telegram\Command\Handler;

use App\AgentRegistry\AgentRegistryInterface;
use App\Telegram\DTO\NormalizedEvent;
use App\Telegram\Service\TelegramSender;

final class AgentDisableHandler
{
    public function __construct(
        private readonly AgentRegistryInterface $agentRegistry,
    ) {
    }

    public function handle(NormalizedEvent $event, TelegramSender $sender, string $agentName): void
    {
        $agent = $this->agentRegistry->findByName($agentName);

        if (null === $agent) {
            $this->reply($event, $sender, sprintf(
                'Агент "%s" не знайдений. Використовуйте /agents для списку.',
                $agentName,
            ));

            return;
        }

        if (!($agent['enabled'] ?? false)) {
            $this->reply($event, $sender, sprintf('Агент %s вже вимкнений.', $agentName));

            return;
        }

        $result = $this->agentRegistry->disable($agentName);

        if ($result) {
            $this->reply($event, $sender, sprintf('Агент %s вимкнений.', $agentName));
        } else {
            $this->reply($event, $sender, sprintf('Не вдалося вимкнути агента %s.', $agentName));
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
