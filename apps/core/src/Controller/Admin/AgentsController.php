<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\AgentRegistry\AgentRegistryInterface;
use App\Security\AdminUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class AgentsController extends AbstractController
{
    public function __construct(
        private readonly AgentRegistryInterface $registry,
    ) {
    }

    #[Route('/admin/agents', name: 'admin_agents')]
    public function __invoke(#[CurrentUser] AdminUser $user): Response
    {
        $agents = array_map(static function (array $agent): array {
            if (is_string($agent['manifest'])) {
                $agent['manifest'] = json_decode($agent['manifest'], true, 512, JSON_THROW_ON_ERROR);
            }
            if (is_string($agent['violations'])) {
                $agent['violations'] = json_decode($agent['violations'], true, 512, JSON_THROW_ON_ERROR);
            }
            if (!is_array($agent['violations'])) {
                $agent['violations'] = [];
            }
            if (is_string($agent['config'])) {
                $agent['config_decoded'] = json_decode($agent['config'], true, 512, JSON_THROW_ON_ERROR);
            } else {
                $agent['config_decoded'] = $agent['config'];
            }
            if (!is_array($agent['config_decoded'])) {
                $agent['config_decoded'] = [];
            }

            return $agent;
        }, $this->registry->findAll());

        return $this->render('admin/agents.html.twig', [
            'agents' => $agents,
            'username' => $user->getUserIdentifier(),
        ]);
    }
}
