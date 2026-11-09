<?php

namespace App\Repository;

use App\Entity\Friend;
use App\Entity\Item;
use App\Entity\Loan;
use App\Enum\LoanStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Loan> */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    /**
     * Prêts en cours : les retards d'abord, puis par date de retour la plus proche,
     * les prêts sans date de retour à la fin.
     *
     * @return list<Loan>
     */
    public function findActive(): array
    {
        return $this->withRelations()
            ->addSelect('CASE WHEN l.dueAt < :today THEN 0 ELSE 1 END AS HIDDEN lateFirst')
            ->addSelect('CASE WHEN l.dueAt IS NULL THEN 1 ELSE 0 END AS HIDDEN noDueLast')
            ->where('l.returnedAt IS NULL')
            ->setParameter('today', new \DateTimeImmutable('today'), 'date_immutable')
            ->orderBy('lateFirst')
            ->addOrderBy('noDueLast')
            ->addOrderBy('l.dueAt')
            ->addOrderBy('l.loanedAt')
            ->getQuery()
            ->getResult();
    }

    /** @return array<int, Loan> prêts en cours indexés par id d'objet */
    public function findActiveIndexedByItem(): array
    {
        $indexed = [];
        foreach ($this->findActive() as $loan) {
            $indexed[$loan->getItem()->getId()] = $loan;
        }

        return $indexed;
    }

    /** @return list<Loan> */
    public function findHistory(?Friend $friend = null, ?LoanStatus $status = null): array
    {
        $qb = $this->withRelations()->orderBy('l.loanedAt', 'DESC')->addOrderBy('l.id', 'DESC');

        if ($friend !== null) {
            $qb->andWhere('l.friend = :friend')->setParameter('friend', $friend);
        }

        match ($status) {
            LoanStatus::Rendu => $qb->andWhere('l.returnedAt IS NOT NULL'),
            LoanStatus::EnRetard => $qb->andWhere('l.returnedAt IS NULL AND l.dueAt < :today'),
            LoanStatus::EnCours => $qb->andWhere('l.returnedAt IS NULL AND (l.dueAt IS NULL OR l.dueAt >= :today)'),
            null => null,
        };
        if ($status !== null && $status !== LoanStatus::Rendu) {
            $qb->setParameter('today', new \DateTimeImmutable('today'), 'date_immutable');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Vérifie s'il existe un prêt en cours pour cet objet. Avec $lock, la ligne est
     * verrouillée (SELECT … FOR UPDATE) jusqu'à la fin de la transaction.
     */
    public function hasActiveLoanFor(Item $item, bool $lock = false): bool
    {
        $query = $this->createQueryBuilder('l')
            ->select('l.id')
            ->where('l.item = :item AND l.returnedAt IS NULL')
            ->setParameter('item', $item)
            ->getQuery();

        if ($lock) {
            $query->setLockMode(LockMode::PESSIMISTIC_WRITE);
        }

        return $query->getOneOrNullResult() !== null;
    }

    /** @return array{active: int, overdue: int, returned: int, total: int} */
    public function getStats(): array
    {
        $row = $this->createQueryBuilder('l')
            ->select('COUNT(l.id) AS total')
            ->addSelect('SUM(CASE WHEN l.returnedAt IS NULL THEN 1 ELSE 0 END) AS active')
            ->addSelect('SUM(CASE WHEN l.returnedAt IS NULL AND l.dueAt < :today THEN 1 ELSE 0 END) AS overdue')
            ->addSelect('SUM(CASE WHEN l.returnedAt IS NOT NULL THEN 1 ELSE 0 END) AS returned')
            ->setParameter('today', new \DateTimeImmutable('today'), 'date_immutable')
            ->getQuery()
            ->getSingleResult();

        return array_map('intval', $row);
    }

    /**
     * Les amis qui empruntent le plus, avec le nombre de retours à l'heure.
     *
     * @return list<array{friend: Friend, loans: int, returned: int, onTime: int}>
     */
    public function findTopBorrowers(int $limit = 5): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('f AS friend, COUNT(l.id) AS loans')
            ->addSelect('SUM(CASE WHEN l.returnedAt IS NOT NULL THEN 1 ELSE 0 END) AS returned')
            ->addSelect('SUM(CASE WHEN l.returnedAt IS NOT NULL AND (l.dueAt IS NULL OR l.returnedAt <= l.dueAt) THEN 1 ELSE 0 END) AS onTime')
            ->from(Friend::class, 'f')
            ->join('f.loans', 'l')
            ->groupBy('f.id')
            ->orderBy('loans', 'DESC')
            ->addOrderBy('f.name')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $r) => [
            'friend' => $r['friend'],
            'loans' => (int) $r['loans'],
            'returned' => (int) $r['returned'],
            'onTime' => (int) $r['onTime'],
        ], $rows);
    }

    private function withRelations(): QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->addSelect('i', 'f')
            ->join('l.item', 'i')
            ->join('l.friend', 'f');
    }
}
