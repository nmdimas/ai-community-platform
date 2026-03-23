<?php

declare(strict_types=1);

namespace App\Command;

use App\Telegram\Api\TelegramApiClient;
use App\Telegram\Service\TelegramBotRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:telegram:webhook-info', description: 'Display Telegram webhook status for a bot')]
final class TelegramWebhookInfoCommand extends Command
{
    public function __construct(
        private readonly TelegramApiClient $apiClient,
        private readonly TelegramBotRegistry $botRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('bot-id', InputArgument::REQUIRED, 'Bot ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $botId = (string) $input->getArgument('bot-id');

        $bot = $this->botRegistry->getBot($botId);
        if (!$bot) {
            $output->writeln(sprintf('<error>Bot "%s" not found</error>', $botId));

            return Command::FAILURE;
        }

        $result = $this->apiClient->getWebhookInfo((string) $bot['bot_token']);

        if (!($result['ok'] ?? false)) {
            $output->writeln(sprintf('<error>Failed: %s</error>', $result['description'] ?? 'unknown'));

            return Command::FAILURE;
        }

        $info = $result['result'] ?? [];

        $output->writeln(sprintf('<info>Bot:</info> @%s (%s)', $bot['bot_username'], $botId));
        $output->writeln(sprintf('<info>URL:</info> %s', $info['url'] ?? '(not set)'));
        $output->writeln(sprintf('<info>Pending updates:</info> %d', $info['pending_update_count'] ?? 0));
        $output->writeln(sprintf('<info>Max connections:</info> %d', $info['max_connections'] ?? 0));

        if (isset($info['last_error_date'])) {
            $output->writeln(sprintf('<error>Last error:</error> %s at %s',
                $info['last_error_message'] ?? 'unknown',
                date('Y-m-d H:i:s', (int) $info['last_error_date']),
            ));
        }

        if (isset($info['allowed_updates'])) {
            $output->writeln(sprintf('<info>Allowed updates:</info> %s', implode(', ', (array) $info['allowed_updates'])));
        }

        return Command::SUCCESS;
    }
}
