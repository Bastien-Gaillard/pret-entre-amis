<?php

declare(strict_types=1);

namespace App\Repository;

use DomainException;
use PDO;
use PDOException;

final class LoanRepository
{
    /** Le statut est calculé en SQL plutôt que stocké : il ne peut jamais être désynchronisé. */
    private const STATUS_SQL = "CASE
            WHEN l.returned_at IS NOT NULL THEN 'rendu'
            WHEN l.due_at < CURDATE()      THEN 'en_retard'
            ELSE 'en_cours'
        END";

    private const SELECT = 'SELECT l.*, i.name AS item_name, i.category, f.name AS friend_name, '
        . self::STATUS_SQL . ' AS status
        FROM loans l
        JOIN items i   ON i.id = l.item_id
        JOIN friends f ON f.id = l.friend_id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Prêts en cours, les retards d'abord puis par date de retour la plus proche. */
    public function active(): array
    {
        return $this->pdo->query(
            self::SELECT . ' WHERE l.returned_at IS NULL
             ORDER BY (l.due_at < CURDATE()) DESC, l.due_at IS NULL, l.due_at, l.loaned_at'
        )->fetchAll();
    }

    /** Historique filtrable par ami et/ou statut. */
    public function history(?int $friendId = null, ?string $status = null): array
    {
        $where = [];
        $params = [];
        if ($friendId !== null) {
            $where[] = 'l.friend_id = :friend';
            $params['friend'] = $friendId;
        }
        if ($status !== null) {
            $where[] = self::STATUS_SQL . ' = :status';
            $params['status'] = $status;
        }

        $sql = self::SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY l.loaned_at DESC, l.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE l.id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Crée un prêt dans une transaction. Le verrou FOR UPDATE évite que deux
     * requêtes simultanées prêtent le même objet ; l'index unique sur
     * active_item_id sert de filet de sécurité au niveau de la base.
     *
     * @throws DomainException si l'objet est déjà prêté
     */
    public function create(int $itemId, int $friendId, string $loanedAt, ?string $dueAt, ?string $notes): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM loans WHERE item_id = ? AND returned_at IS NULL FOR UPDATE');
            $stmt->execute([$itemId]);
            if ($stmt->fetch()) {
                throw new DomainException('Cet objet est déjà prêté.');
            }

            $this->pdo->prepare(
                'INSERT INTO loans (item_id, friend_id, loaned_at, due_at, notes) VALUES (?, ?, ?, ?, ?)'
            )->execute([$itemId, $friendId, $loanedAt, $dueAt, $notes]);
            $id = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            return $id;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            if ($e->getCode() === '23000') {
                throw new DomainException('Cet objet est déjà prêté.', 0, $e);
            }
            throw $e;
        } catch (DomainException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @return bool false si le prêt n'existe pas ou était déjà rendu */
    public function markReturned(int $id, string $returnedAt): bool
    {
        $stmt = $this->pdo->prepare('UPDATE loans SET returned_at = ? WHERE id = ? AND returned_at IS NULL');
        $stmt->execute([$returnedAt, $id]);

        return $stmt->rowCount() === 1;
    }

    /** @return array{active: int, overdue: int, total: int, returned: int} */
    public function stats(): array
    {
        $row = $this->pdo->query(
            'SELECT COUNT(*)                                          AS total,
                    COALESCE(SUM(returned_at IS NULL), 0)                    AS active,
                    COALESCE(SUM(returned_at IS NULL AND due_at < CURDATE()), 0) AS overdue,
                    COALESCE(SUM(returned_at IS NOT NULL), 0)                AS returned
             FROM loans'
        )->fetch();

        return array_map('intval', $row);
    }

    /** Les amis qui empruntent le plus, avec leur taux de retour à l'heure. */
    public function topBorrowers(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.id, f.name,
                    COUNT(*) AS loans,
                    SUM(l.returned_at IS NOT NULL AND (l.due_at IS NULL OR l.returned_at <= l.due_at)) AS on_time,
                    SUM(l.returned_at IS NOT NULL) AS returned
             FROM loans l
             JOIN friends f ON f.id = l.friend_id
             GROUP BY f.id, f.name
             ORDER BY loans DESC, f.name
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
