<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class FriendRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Liste des amis avec le nombre de prêts en cours et au total. */
    public function allWithStats(): array
    {
        return $this->pdo->query(
            'SELECT f.*,
                    COUNT(l.id)                                           AS total_loans,
                    COALESCE(SUM(l.id IS NOT NULL AND l.returned_at IS NULL), 0) AS active_loans
             FROM friends f
             LEFT JOIN loans l ON l.friend_id = f.id
             GROUP BY f.id
             ORDER BY f.name'
        )->fetchAll();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT id, name FROM friends ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM friends WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** @param array{name: string, email: ?string, phone: ?string} $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO friends (name, email, phone) VALUES (:name, :email, :phone)');
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array{name: string, email: ?string, phone: ?string} $data */
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE friends SET name = :name, email = :email, phone = :phone WHERE id = :id');
        $stmt->execute($data + ['id' => $id]);
    }

    /** Lève une PDOException (23000) si l'ami a un historique de prêts. */
    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM friends WHERE id = ?')->execute([$id]);
    }
}
