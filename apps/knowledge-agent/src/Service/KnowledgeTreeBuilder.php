<?php

declare(strict_types=1);

namespace App\Service;

use App\OpenSearch\KnowledgeRepository;

final class KnowledgeTreeBuilder
{
    public function __construct(
        private readonly KnowledgeRepository $repository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $flatPaths = $this->repository->aggregateTree();
        $tree = [];

        foreach ($flatPaths as $path => $count) {
            $segments = explode('/', (string) $path);
            $this->insertIntoTree($tree, $segments, (int) $count);
        }

        return $tree;
    }

    /**
     * @param array<string, mixed> $tree
     * @param list<string>         $segments
     */
    private function insertIntoTree(array &$tree, array $segments, int $count): void
    {
        if ([] === $segments) {
            return;
        }

        $segment = array_shift($segments);

        if (!isset($tree[$segment])) {
            $tree[$segment] = ['count' => 0, 'children' => []];
        }

        $tree[$segment]['count'] += $count;

        if ([] !== $segments) {
            $this->insertIntoTree($tree[$segment]['children'], $segments, $count);
        }
    }
}
