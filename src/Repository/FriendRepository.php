<?php

namespace App\Repository;

use App\Entity\Friend;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Friend> */
class FriendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friend::class);
    }

    /**
     * Amis triés par nom, avec leurs compteurs de prêts.
     *
     * @return list<array{friend: Friend, total: int, active: int}>
     */
    public function findAllWithStats(): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('f AS friend')
            ->addSelect('COUNT(l.id) AS total')
            ->addSelect('SUM(CASE WHEN l.id IS NOT NULL AND l.returnedAt IS NULL THEN 1 ELSE 0 END) AS active')
            ->leftJoin('f.loans', 'l')
            ->groupBy('f.id')
            ->orderBy('f.name')
            ->getQuery()
            ->getResult();

        return array_map(static fn (array $r) => [
            'friend' => $r['friend'],
            'total' => (int) $r['total'],
            'active' => (int) $r['active'],
        ], $rows);
    }

    public function orderedByNameQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('f')->orderBy('f.name');
    }
}
