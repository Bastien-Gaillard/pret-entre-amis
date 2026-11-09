<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\HttpException;
use App\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testPasseLeParametreDeRouteAuHandler(): void
    {
        $router = new Router();
        $received = null;
        $router->post('/loans/{id}/return', function (int $id) use (&$received): void {
            $received = $id;
        });

        $router->dispatch('POST', '/loans/42/return?x=1');

        self::assertSame(42, $received);
    }

    public function testRenvoie404SiAucuneRouteNeCorrespond(): void
    {
        $this->expectExceptionObject(new HttpException(404));

        (new Router())->dispatch('GET', '/inconnu');
    }

    public function testRenvoie405SiLaMethodeNeCorrespondPas(): void
    {
        $router = new Router();
        $router->get('/items', fn () => null);

        $this->expectExceptionObject(new HttpException(405));

        $router->dispatch('POST', '/items');
    }
}
