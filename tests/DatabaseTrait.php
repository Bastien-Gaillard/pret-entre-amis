<?php

namespace App\Tests;

use App\Entity\Friend;
use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;

/** Vide les tables avant chaque test et fournit des helpers de création. */
trait DatabaseTrait
{
    protected function resetDatabase(EntityManagerInterface $em): void
    {
        $connection = $em->getConnection();
        foreach (['loans', 'items', 'friends'] as $table) {
            $connection->executeStatement("DELETE FROM $table");
        }
        $em->clear();
    }

    protected function createFriend(EntityManagerInterface $em, string $name = 'Julien'): Friend
    {
        $friend = new Friend($name);
        $em->persist($friend);
        $em->flush();

        return $friend;
    }

    protected function createItem(EntityManagerInterface $em, string $name = 'Perceuse'): Item
    {
        $item = new Item($name);
        $em->persist($item);
        $em->flush();

        return $item;
    }
}
