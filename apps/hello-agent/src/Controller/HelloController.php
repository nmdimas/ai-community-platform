<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HelloController extends AbstractController
{
    private const DEFAULT_GREETING = 'Hello, World!';

    #[Route('/', name: 'hello', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('hello.html.twig', [
            'greeting' => self::DEFAULT_GREETING,
        ]);
    }
}
