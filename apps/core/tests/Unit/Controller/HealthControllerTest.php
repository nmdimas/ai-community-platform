<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\HealthController;
use Codeception\Test\Unit;
use Symfony\Component\HttpFoundation\JsonResponse;

class HealthControllerTest extends Unit
{
    public function testReturnsOkResponse(): void
    {
        $controller = new HealthController();
        $response = ($controller)();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        /** @var array{status: string, service: string, version: string} $data */
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame('ok', $data['status']);
        $this->assertSame('core-platform', $data['service']);
        $this->assertSame('0.1.0', $data['version']);
    }

    public function testResponseContentTypeIsJson(): void
    {
        $controller = new HealthController();
        $response = ($controller)();

        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }
}
