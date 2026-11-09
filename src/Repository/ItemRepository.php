<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ItemRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Tous les objets, avec l'emprunteur actuel s'il y en a un. */
    public function allWithCurrentLoan(?string $category = null): array
    {
        $sql = 'SELECT i.*, l.id AS loan_id, l.due_at, f.id AS borrower_id, f.name AS borrower_name
                FROM items i
                LEFT JOIN loans l   ON l.item_id = i.id AND l.returned_at IS NULL
                LEFT JOIN friends f ON f.id = l.friend_id';
        $params = [];
        if ($category !== null && $category !== '') {
            $sql .= ' WHERE i.category = ?';
            $params[] = $category;
        }
        $stmt = $this->pdo->prepare($sql . ' ORDER BY i.name');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** Objets qui ne sont pas actuellement prêtés. */
    public function available(): array
    {
        return $this->pdo->query(
            'SELECT i.id, i.name, i.category
             FROM items i
             WHERE NOT EXISTS (SELECT 1 FROM loans l WHERE l.item_id = i.id AND l.returned_at IS NULL)
             ORDER BY i.category, i.name'
        )->fetchAll();
    }

    /** @return list<string> */
    public function categories(): array
    {
        return $this->pdo->query('SELECT DISTINCT category FROM items ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** @param array{name: string, category: string, notes: ?string} $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO items (name, category, notes) VALUES (:name, :category, :notes)');
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array{name: string, category: string, notes: ?string} $data */
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE items SET name = :name, category = :category, notes = :notes WHERE id = :id');
        $stmt->execute($data + ['id' => $id]);
    }

    /** Lève une PDOException (23000) si l'objet a un historique de prêts. */
    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
    }
}
