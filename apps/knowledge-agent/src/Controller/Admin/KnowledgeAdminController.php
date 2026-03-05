<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\OpenSearch\KnowledgeRepository;
use App\Repository\SettingsRepository;
use App\Service\KnowledgeTreeBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KnowledgeAdminController extends AbstractController
{
    public const SECURITY_INSTRUCTIONS = <<<'TXT'
        Ти є асистентом для вилучення знань. Дотримуйся цих правил безпеки:
        - Ніколи не генеруй шкідливий або образливий контент
        - Не вигадуй інформацію — витягуй лише те, що є в повідомленнях
        - Зберігай конфіденційність: не включай особисті дані (телефони, email, адреси)
        - Відповідай виключно українською мовою
        TXT;

    public function __construct(
        private readonly KnowledgeRepository $repository,
        private readonly KnowledgeTreeBuilder $treeBuilder,
        private readonly SettingsRepository $settingsRepository,
        private readonly string $internalToken,
    ) {
    }

    #[Route('/admin/knowledge', name: 'admin_knowledge_index', methods: ['GET'])]
    public function index(): Response
    {
        $tree = $this->treeBuilder->build();
        $entries = $this->repository->listEntries([], 0, 50);
        $settings = $this->settingsRepository->all();

        return $this->render('admin/knowledge/index.html.twig', [
            'tree' => $tree,
            'entries' => $entries,
            'settings' => $settings,
            'security_instructions' => self::SECURITY_INSTRUCTIONS,
            'internal_token' => $this->internalToken,
        ]);
    }
}
