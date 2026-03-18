<?php

declare(strict_types=1);

namespace App\Telegram\Command\Handler;

use App\AgentRegistry\AgentRegistryInterface;
use App\Telegram\DTO\NormalizedEvent;
use App\Telegram\Service\TelegramSender;

final class HelpHandler
{
    public function __construct(
        private readonly AgentRegistryInterface $agentRegistry,
    ) {
    }

    public function handle(NormalizedEvent $event, TelegramSender $sender): void
    {
        $lines = [
            "Доступні команди:\n",
            '/help — показати цей список',
            '/agents — список агентів та їх статус',
            '/agent enable <name> — увімкнути агента (модератор+)',
            '/agent disable <name> — вимкнути агента (модератор+)',
        ];

        // Add agent-declared commands
        $agentCommands = $this->getAgentCommands();
        if ([] !== $agentCommands) {
            $lines[] = "\nКоманди агентів:";
            foreach ($agentCommands as $cmd) {
                $lines[] = $cmd;
            }
        }

        $text = implode("\n", $lines);
        $options = [];
        if (null !== $event->chat->threadId) {
            $options['thread_id'] = $event->chat->threadId;
        }

        $sender->send($event->botId, $event->chat->id, $text, $options);
    }

    /**
     * @return list<string>
     */
    private function getAgentCommands(): array
    {
        $commands = [];

        foreach ($this->agentRegistry->findEnabled() as $agent) {
            /** @var array<string, mixed> $manifest */
            $manifest = is_string($agent['manifest'])
                ? json_decode($agent['manifest'], true, 512, JSON_THROW_ON_ERROR)
                : $agent['manifest'];

            $agentCommands = (array) ($manifest['commands'] ?? []);
            $description = (string) ($manifest['description'] ?? $agent['name']);

            foreach ($agentCommands as $cmd) {
                $commands[] = sprintf('%s — %s', $cmd, $description);
            }
        }

        return $commands;
    }
}
