<?php

declare(strict_types=1);

namespace App\Controller\Wiki;

use App\OpenSearch\KnowledgeRepository;
use App\Repository\SettingsRepository;
use App\Service\KnowledgeTreeBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WikiController extends AbstractController
{
    public function __construct(
        private readonly KnowledgeRepository $repository,
        private readonly KnowledgeTreeBuilder $treeBuilder,
        private readonly SettingsRepository $settings,
    ) {
    }

    #[Route('/wiki', name: 'wiki_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if ('1' !== $this->settings->get('encyclopedia_enabled', '1')) {
            return new Response('Енциклопедія тимчасово недоступна', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $tree = $this->treeBuilder->build();
        $query = (string) $request->query->get('q', '');

        $entries = [];
        if ('' !== $query) {
            $entries = $this->repository->search($query);
        }

        return $this->render('wiki/index.html.twig', [
            'tree' => $tree,
            'query' => $query,
            'entries' => $entries,
        ]);
    }

    #[Route('/wiki/entry/{id}', name: 'wiki_entry', methods: ['GET'])]
    public function entry(string $id): Response
    {
        if ('1' !== $this->settings->get('encyclopedia_enabled', '1')) {
            return new Response('Енциклопедія тимчасово недоступна', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $entry = $this->repository->get($id);

        if (null === $entry) {
            throw $this->createNotFoundException('Запис не знайдено');
        }

        $tree = $this->treeBuilder->build();

        return $this->render('wiki/entry.html.twig', [
            'entry' => $entry,
            'tree' => $tree,
        ]);
    }
}
