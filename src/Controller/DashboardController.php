<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Enum\LoanStatus;
use App\Repository\LoanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(LoanRepository $loans): Response
    {
        $active = $loans->findActive();

        return $this->render('dashboard/index.html.twig', [
            'overdue' => array_values(array_filter($active, fn (Loan $l) => $l->getStatus() === LoanStatus::EnRetard)),
            'ongoing' => array_values(array_filter($active, fn (Loan $l) => $l->getStatus() !== LoanStatus::EnRetard)),
            'stats' => $loans->getStats(),
            'topBorrowers' => $loans->findTopBorrowers(),
        ]);
    }
}
