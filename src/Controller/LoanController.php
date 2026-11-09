<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Enum\LoanStatus;
use App\Exception\ItemAlreadyLentException;
use App\Form\LoanType;
use App\Repository\FriendRepository;
use App\Repository\ItemRepository;
use App\Repository\LoanRepository;
use App\Service\LoanManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prets')]
final class LoanController extends AbstractController
{
    #[Route('', name: 'app_loan_history', methods: ['GET'])]
    public function history(Request $request, LoanRepository $loans, FriendRepository $friends): Response
    {
        $friend = $request->query->getInt('ami') > 0 ? $friends->find($request->query->getInt('ami')) : null;
        $status = LoanStatus::tryFrom($request->query->getString('statut'));

        return $this->render('loan/history.html.twig', [
            'loans' => $loans->findHistory($friend, $status),
            'friends' => $friends->findBy([], ['name' => 'ASC']),
            'currentFriend' => $friend,
            'currentStatus' => $status,
            'statuses' => LoanStatus::cases(),
        ]);
    }

    #[Route('/nouveau', name: 'app_loan_new', methods: ['GET', 'POST'])]
    public function new(Request $request, LoanManager $manager, ItemRepository $items, FriendRepository $friends): Response
    {
        $loan = (new Loan())->setDueAt(new \DateTimeImmutable('today +3 weeks'));
        // Pré-remplissage depuis les boutons « Prêter » des listes d'objets et d'amis.
        if ($itemId = $request->query->getInt('objet')) {
            $loan->setItem($items->find($itemId));
        }
        if ($friendId = $request->query->getInt('ami')) {
            $loan->setFriend($friends->find($friendId));
        }

        $form = $this->createForm(LoanType::class, $loan)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $manager->lend($loan);
                $this->addFlash('success', sprintf('« %s » est prêté à %s.', $loan->getItem()->getName(), $loan->getFriend()->getName()));

                return $this->redirectToRoute('app_dashboard', status: Response::HTTP_SEE_OTHER);
            } catch (ItemAlreadyLentException $e) {
                $form->get('item')->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('loan/new.html.twig', [
            'form' => $form,
            'hasItems' => $items->availableQueryBuilder()->select('COUNT(i.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult() > 0,
            'hasFriends' => $friends->count() > 0,
        ]);
    }

    #[Route('/{id}/rendu', name: 'app_loan_return', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function markReturned(Request $request, Loan $loan, LoanManager $manager): Response
    {
        if (!$this->isCsrfTokenValid('return-loan-' . $loan->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($loan->isReturned()) {
            $this->addFlash('error', 'Ce prêt était déjà marqué comme rendu.');
        } else {
            $manager->markReturned($loan);
            $this->addFlash('success', sprintf('« %s » est revenu de chez %s.', $loan->getItem()->getName(), $loan->getFriend()->getName()));
        }

        $back = $request->getPayload()->getString('back');

        return $this->redirect(str_starts_with($back, '/') && !str_starts_with($back, '//') ? $back : $this->generateUrl('app_dashboard'), Response::HTTP_SEE_OTHER);
    }
}
