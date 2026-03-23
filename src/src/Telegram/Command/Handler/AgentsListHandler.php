<?php

declare(strict_types=1);

namespace App\Telegram\Command\Handler;

use App\AgentRegistry\AgentRegistryInterface;
use App\Telegram\DTO\NormalizedEvent;
use App\Telegram\Service\TelegramSender;

final class AgentsListHandler
{
    public function __construct(
        private readonly AgentRegistryInterface $agentRegistry,
    ) {
    }

    public function handle(NormalizedEvent $event, TelegramSender $sender): void
    {
        $agents = $this->agentRegistry->findAll();

        if ([] === $agents) {
            $this->reply($event, $sender, 'Агентів не зареєстровано.');

            return;
        }

        $lines = ["Зареєстровані агенти:\n"];

        foreach ($agents as $agent) {
            $enabled = (bool) ($agent['enabled'] ?? false);
            $status = $enabled ? '🟢' : '🔴';
            $name = (string) ($agent['name'] ?? 'unknown');

            /** @var array<string, mixed> $manifest */
            $manifest = is_string($agent['manifest'] ?? null)
                ? json_decode((string) $agent['manifest'], true, 512, JSON_THROW_ON_ERROR)
                : ($agent['manifest'] ?? []);

            $description = (string) ($manifest['description'] ?? '');
            $line = sprintf('%s %s', $status, $name);
            if ('' !== $description) {
                $line .= sprintf(' — %s', $description);
            }

            $lines[] = $line;
        }

        $this->reply($event, $sender, implode("\n", $lines));
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
