<?php

namespace App\Service;

use App\Entity\Loan;
use App\Exception\ItemAlreadyLentException;
use App\Repository\LoanRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

/** Règles métier autour des prêts. */
final class LoanManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoanRepository $loans,
    ) {
    }

    /**
     * Enregistre un prêt. La vérification se fait dans une transaction avec verrou
     * pour éviter que deux requêtes simultanées prêtent le même objet ; l'index
     * unique en base reste le dernier filet de sécurité.
     *
     * @throws ItemAlreadyLentException
     */
    public function lend(Loan $loan): void
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();
        try {
            if ($this->loans->hasActiveLoanFor($loan->getItem(), lock: true)) {
                throw new ItemAlreadyLentException();
            }
            $this->em->persist($loan);
            $this->em->flush();
            $connection->commit();
        } catch (UniqueConstraintViolationException $e) {
            $this->rollBack();
            throw new ItemAlreadyLentException($e);
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function markReturned(Loan $loan, ?\DateTimeImmutable $on = null): void
    {
        $loan->markReturned($on ?? new \DateTimeImmutable('today'));
        $this->em->flush();
    }

    private function rollBack(): void
    {
        $connection = $this->em->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }
    }
}
