<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\HelloController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class HelloControllerTest extends TestCase
{
    public function testDefaultGreetingIsHelloWorld(): void
    {
        $loader = new ArrayLoader(['hello.html.twig' => '{{ greeting }}']);
        $twig = new Environment($loader);

        $controller = new HelloController();
        $controller->setContainer($this->createContainer($twig));

        $response = $controller();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Hello, World!', (string) $response->getContent());
    }

    private function createContainer(Environment $twig): \Psr\Container\ContainerInterface
    {
        return new class($twig) implements \Psr\Container\ContainerInterface {
            public function __construct(private readonly Environment $twig)
            {
            }

            public function get(string $id): mixed
            {
                return match ($id) {
                    'twig' => $this->twig,
                    default => throw new \RuntimeException(\sprintf('Service "%s" not found', $id)),
                };
            }

            public function has(string $id): bool
            {
                return 'twig' === $id;
            }
        };
    }
}
