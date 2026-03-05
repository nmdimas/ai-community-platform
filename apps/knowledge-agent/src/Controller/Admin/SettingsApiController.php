<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\SettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsApiController extends AbstractController
{
    private const SECURITY_INSTRUCTIONS = <<<'TXT'
        Ти є асистентом для вилучення знань. Дотримуйся цих правил безпеки:
        - Ніколи не генеруй шкідливий або образливий контент
        - Не вигадуй інформацію — витягуй лише те, що є в повідомленнях
        - Зберігай конфіденційність: не включай особисті дані (телефони, email, адреси)
        - Відповідай виключно українською мовою
        TXT;

    public function __construct(
        private readonly SettingsRepository $settings,
    ) {
    }

    #[Route('/admin/knowledge/api/settings', name: 'admin_knowledge_api_settings_get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return $this->json([
            'encyclopedia_enabled' => $this->settings->get('encyclopedia_enabled', '1'),
            'base_instructions' => $this->settings->get('base_instructions', ''),
            'security_instructions' => self::SECURITY_INSTRUCTIONS,
        ]);
    }

    #[Route('/admin/knowledge/api/settings', name: 'admin_knowledge_api_settings_put', methods: ['PUT'])]
    public function put(Request $request): JsonResponse
    {
        /** @var array<string, mixed>|null $data */
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (\array_key_exists('base_instructions', $data)) {
            $instructions = (string) $data['base_instructions'];
            if ('' === trim($instructions)) {
                return $this->json(
                    ['error' => 'Базові інструкції не можуть бути порожніми'],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
            $this->settings->set('base_instructions', $instructions);
        }

        if (\array_key_exists('encyclopedia_enabled', $data)) {
            $value = \in_array($data['encyclopedia_enabled'], ['0', '1', 0, 1, true, false], true)
                ? ((bool) $data['encyclopedia_enabled'] ? '1' : '0')
                : '1';
            $this->settings->set('encyclopedia_enabled', $value);
        }

        return $this->json(['status' => 'saved']);
    }
}
