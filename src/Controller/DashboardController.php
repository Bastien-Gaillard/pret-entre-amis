<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LoanRepository;
use App\View;

final class DashboardController
{
    public function __construct(
        private readonly View $view,
        private readonly LoanRepository $loans,
    ) {
    }

    public function index(): void
    {
        $this->view->render('dashboard', [
            'title'        => 'Tableau de bord',
            'activeLoans'  => $this->loans->active(),
            'stats'        => $this->loans->stats(),
            'topBorrowers' => $this->loans->topBorrowers(),
        ]);
    }
}
