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

#[AsCommand(name: 'app:telegram:delete-webhook', description: 'Delete Telegram webhook for a bot')]
final class TelegramDeleteWebhookCommand extends Command
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

        $result = $this->apiClient->deleteWebhook((string) $bot['bot_token']);

        if ($result['ok'] ?? false) {
            $this->botRegistry->updateBot($botId, ['webhook_url' => null]);
            $output->writeln('<info>Webhook deleted.</info>');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('<error>Failed: %s</error>', $result['description'] ?? 'unknown error'));

        return Command::FAILURE;
    }
}
