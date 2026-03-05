<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Security\AdminUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChatsController extends AbstractController
{
    #[Route('/admin/chats', name: 'admin_chats')]
    public function __invoke(#[CurrentUser] AdminUser $user): Response
    {
        return $this->render('admin/chats.html.twig', [
            'username' => $user->getUserIdentifier(),
        ]);
    }
}
