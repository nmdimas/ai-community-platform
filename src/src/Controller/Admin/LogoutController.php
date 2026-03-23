<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Component\Routing\Attribute\Route;

final class LogoutController
{
    #[Route('/admin/logout', name: 'admin_logout')]
    public function __invoke(): never
    {
        throw new \LogicException('This method should never be reached; Symfony Security intercepts it.');
    }
}
