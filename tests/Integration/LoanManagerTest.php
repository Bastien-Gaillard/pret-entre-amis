<?php

namespace App\Tests\Integration;

use App\Entity\Loan;
use App\Exception\ItemAlreadyLentException;
use App\Repository\LoanRepository;
use App\Service\LoanManager;
use App\Tests\DatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Règles métier testées sur une vraie base MySQL (prets_test). */
final class LoanManagerTest extends KernelTestCase
{
    use DatabaseTrait;

    private EntityManagerInterface $em;
    private LoanManager $manager;

    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->manager = $container->get(LoanManager::class);
        $this->resetDatabase($this->em);
    }

    public function testRefuseDePreterUnObjetDejaPrete(): void
    {
        $item = $this->createItem($this->em);
        $friend = $this->createFriend($this->em);
        $this->manager->lend((new Loan())->setItem($item)->setFriend($friend));

        $this->expectException(ItemAlreadyLentException::class);
        $this->manager->lend((new Loan())->setItem($item)->setFriend($friend));
    }

    public function testLaBaseRefuseElleAussiUnDoublePret(): void
    {
        $item = $this->createItem($this->em);
        $friend = $this->createFriend($this->em);
        $this->manager->lend((new Loan())->setItem($item)->setFriend($friend));

        // Insertion SQL directe, sans passer par le service : l'index unique doit bloquer.
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $this->em->getConnection()->insert('loans', [
            'item_id' => $item->getId(), 'friend_id' => $friend->getId(),
            'loaned_at' => date('Y-m-d'), 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function testUnObjetRenduPeutEtreRepreteEtLesStatsSuivent(): void
    {
        $item = $this->createItem($this->em);
        $friend = $this->createFriend($this->em);
        $first = (new Loan())->setItem($item)->setFriend($friend)
            ->setLoanedAt(new \DateTimeImmutable('-10 days'))
            ->setDueAt(new \DateTimeImmutable('-1 day'));
        $this->manager->lend($first);

        $loans = self::getContainer()->get(LoanRepository::class);
        self::assertSame(['total' => 1, 'active' => 1, 'overdue' => 1, 'returned' => 0], $loans->getStats());

        $this->manager->markReturned($first);
        $this->manager->lend((new Loan())->setItem($item)->setFriend($friend));

        self::assertSame(['total' => 2, 'active' => 1, 'overdue' => 0, 'returned' => 1], $loans->getStats());
    }
}
