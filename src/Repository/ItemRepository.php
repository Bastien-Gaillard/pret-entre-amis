<?php

namespace App\Repository;

use App\Entity\Item;
use App\Entity\Loan;
use App\Enum\ItemCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Item> */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /** @return list<Item> */
    public function findByCategory(?ItemCategory $category): array
    {
        $qb = $this->createQueryBuilder('i')->orderBy('i.name');
        if ($category !== null) {
            $qb->where('i.category = :category')->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    /** Objets qui ne sont pas prêtés en ce moment (pour le formulaire de prêt). */
    public function availableQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('i')
            ->where(sprintf('NOT EXISTS (SELECT l.id FROM %s l WHERE l.item = i AND l.returnedAt IS NULL)', Loan::class))
            ->orderBy('i.category')
            ->addOrderBy('i.name');
    }

    /** @return array<string, int> nombre d'objets par catégorie (valeur de l'enum => total) */
    public function countByCategory(): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('i.category AS category, COUNT(i.id) AS total')
            ->groupBy('i.category')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $key = $row['category'] instanceof ItemCategory ? $row['category']->value : (string) $row['category'];
            $counts[$key] = (int) $row['total'];
        }

        return $counts;
    }
}
