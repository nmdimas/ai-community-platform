<?php

declare(strict_types=1);

namespace App\Command;

use App\Telegram\Api\TelegramApiClient;
use App\Telegram\Service\TelegramBotRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:telegram:set-webhook', description: 'Register Telegram webhook for a bot')]
final class TelegramWebhookCommand extends Command
{
    public function __construct(
        private readonly TelegramApiClient $apiClient,
        private readonly TelegramBotRegistry $botRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('bot-id', InputArgument::REQUIRED, 'Bot ID')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Webhook base URL (e.g., https://example.com)')
            ->addOption('max-connections', null, InputOption::VALUE_OPTIONAL, 'Max simultaneous connections', '40');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $botId = (string) $input->getArgument('bot-id');
        $baseUrl = (string) $input->getOption('url');
        $maxConnections = (int) $input->getOption('max-connections');

        $bot = $this->botRegistry->getBot($botId);
        if (!$bot) {
            $output->writeln(sprintf('<error>Bot "%s" not found</error>', $botId));

            return Command::FAILURE;
        }

        $webhookUrl = rtrim($baseUrl, '/').'/api/v1/webhook/telegram/'.$botId;
        $token = (string) $bot['bot_token'];
        $secret = (string) ($bot['webhook_secret'] ?? '');

        $params = [
            'url' => $webhookUrl,
            'max_connections' => $maxConnections,
            'allowed_updates' => ['message', 'edited_message', 'channel_post', 'edited_channel_post', 'callback_query'],
        ];

        if ('' !== $secret) {
            $params['secret_token'] = $secret;
        }

        $result = $this->apiClient->setWebhook($token, $params);

        if ($result['ok'] ?? false) {
            $this->botRegistry->updateBot($botId, ['webhook_url' => $webhookUrl]);
            $output->writeln(sprintf('<info>Webhook set: %s</info>', $webhookUrl));

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('<error>Failed: %s</error>', $result['description'] ?? 'unknown error'));

        return Command::FAILURE;
    }
}
