<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Input;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    protected function tearDown(): void
    {
        $_POST = [];
    }

    #[DataProvider('dates')]
    public function testValideLesDates(string $saisie, ?string $attendu): void
    {
        $_POST['d'] = $saisie;

        self::assertSame($attendu, Input::date('d'));
    }

    public static function dates(): iterable
    {
        yield 'date valide'       => ['2026-11-09', '2026-11-09'];
        yield '30 février'        => ['2026-02-30', null];
        yield 'format français'   => ['09/11/2026', null];
        yield 'vide'              => ['', null];
    }

    public function testNAccepteQueDesEntiersPositifs(): void
    {
        $_POST = ['ok' => '12', 'zero' => '0', 'texte' => '3abc'];

        self::assertSame(12, Input::int('ok'));
        self::assertNull(Input::int('zero'));
        self::assertNull(Input::int('texte'));
    }
}
