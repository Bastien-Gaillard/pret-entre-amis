<?php

namespace App\Tests\Unit;

use App\Entity\Loan;
use App\Enum\LoanStatus;
use PHPUnit\Framework\TestCase;

final class LoanTest extends TestCase
{
    private \DateTimeImmutable $today;

    protected function setUp(): void
    {
        $this->today = new \DateTimeImmutable('2026-11-09');
    }

    public function testLeStatutDependDeLaDateDeRetour(): void
    {
        $loan = (new Loan())->setLoanedAt(new \DateTimeImmutable('2026-11-01'));
        self::assertSame(LoanStatus::EnCours, $loan->getStatus($this->today), 'Sans date de retour, jamais en retard');

        $loan->setDueAt(new \DateTimeImmutable('2026-11-09'));
        self::assertSame(LoanStatus::EnCours, $loan->getStatus($this->today), 'Le jour J, pas encore en retard');

        $loan->setDueAt(new \DateTimeImmutable('2026-11-08'));
        self::assertSame(LoanStatus::EnRetard, $loan->getStatus($this->today));
        self::assertSame(-1, $loan->daysUntilDue($this->today));

        $loan->markReturned($this->today);
        self::assertSame(LoanStatus::Rendu, $loan->getStatus($this->today));
    }

    public function testUnPretNeSeRendQuUneFois(): void
    {
        $loan = new Loan();
        $loan->markReturned($this->today);

        $this->expectException(\LogicException::class);
        $loan->markReturned($this->today);
    }

    public function testLAvancementEstBorneEntre0Et100(): void
    {
        $loan = (new Loan())
            ->setLoanedAt(new \DateTimeImmutable('2026-11-01'))
            ->setDueAt(new \DateTimeImmutable('2026-11-11'));

        self::assertSame(80, $loan->progress($this->today));
        self::assertSame(100, $loan->progress(new \DateTimeImmutable('2026-12-31')));
    }
}
