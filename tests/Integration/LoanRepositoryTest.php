<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database;
use App\Repository\LoanRepository;
use DomainException;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Tests sur une vraie base MySQL : ce sont les règles métier les plus
 * importantes, et elles reposent en partie sur des contraintes SQL.
 */
final class LoanRepositoryTest extends TestCase
{
    private static PDO $pdo;
    private LoanRepository $loans;
    private int $itemId;
    private int $friendId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();
        self::$pdo->exec((string) file_get_contents(__DIR__ . '/../../database/schema.sql'));
    }

    protected function setUp(): void
    {
        self::$pdo->exec('DELETE FROM loans; DELETE FROM items; DELETE FROM friends');
        self::$pdo->exec("INSERT INTO items (name) VALUES ('Perceuse')");
        $this->itemId = (int) self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO friends (name) VALUES ('Julien')");
        $this->friendId = (int) self::$pdo->lastInsertId();

        $this->loans = new LoanRepository(self::$pdo);
    }

    public function testRefuseDePreterUnObjetDejaPrete(): void
    {
        $this->loans->create($this->itemId, $this->friendId, date('Y-m-d'), null, null);

        $this->expectException(DomainException::class);

        $this->loans->create($this->itemId, $this->friendId, date('Y-m-d'), null, null);
    }

    public function testUnObjetRenduPeutEtreRepreteEtNeSeRendQuUneFois(): void
    {
        $id = $this->loans->create($this->itemId, $this->friendId, date('Y-m-d'), null, null);

        self::assertTrue($this->loans->markReturned($id, date('Y-m-d')));
        self::assertFalse($this->loans->markReturned($id, date('Y-m-d')), 'Un prêt ne se rend pas deux fois');

        $this->loans->create($this->itemId, $this->friendId, date('Y-m-d'), null, null);
        self::assertCount(1, $this->loans->active());
    }

    public function testLeStatutEnRetardEstCalculeDepuisLaDateDeRetour(): void
    {
        $id = $this->loans->create(
            $this->itemId,
            $this->friendId,
            date('Y-m-d', strtotime('-10 days')),
            date('Y-m-d', strtotime('-1 day')),
            null,
        );

        self::assertSame('en_retard', $this->loans->find($id)['status']);
        self::assertSame(1, $this->loans->stats()['overdue']);
    }
}
