<?php

declare(strict_types=1);

namespace App\Command;

use App\OpenSearch\OpenSearchIndexManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'knowledge:index:setup',
    description: 'Create or verify the OpenSearch knowledge index',
)]
final class KnowledgeIndexSetupCommand extends Command
{
    public function __construct(
        private readonly OpenSearchIndexManager $indexManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->indexManager->indexExists()) {
            $io->success('Index already exists — nothing to do.');

            return Command::SUCCESS;
        }

        $io->info('Creating OpenSearch knowledge index...');
        $this->indexManager->createIndex();
        $io->success('Index created successfully.');

        return Command::SUCCESS;
    }
}
