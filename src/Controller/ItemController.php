<?php

namespace App\Controller;

use App\Entity\Item;
use App\Enum\ItemCategory;
use App\Form\ItemType;
use App\Repository\ItemRepository;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/objets')]
final class ItemController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'app_item_index', methods: ['GET'])]
    public function index(Request $request, ItemRepository $items, LoanRepository $loans): Response
    {
        $category = ItemCategory::tryFrom($request->query->getString('categorie'));

        return $this->render('item/index.html.twig', [
            'items' => $items->findByCategory($category),
            'activeLoans' => $loans->findActiveIndexedByItem(),
            'counts' => $items->countByCategory(),
            'current' => $category,
        ]);
    }

    #[Route('/nouveau', name: 'app_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Item(), 'Ajouter un objet');
    }

    #[Route('/{id}/modifier', name: 'app_item_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Item $item): Response
    {
        return $this->handleForm($request, $item, 'Modifier « ' . $item->getName() . ' »');
    }

    #[Route('/{id}/supprimer', name: 'app_item_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Item $item): Response
    {
        if (!$this->isCsrfTokenValid('delete-item-' . $item->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$item->getLoans()->isEmpty()) {
            $this->addFlash('error', sprintf('Impossible de supprimer « %s » : il a un historique de prêts.', $item->getName()));
        } else {
            $this->em->remove($item);
            $this->em->flush();
            $this->addFlash('success', sprintf('« %s » a été supprimé.', $item->getName()));
        }

        return $this->redirectToRoute('app_item_index', status: Response::HTTP_SEE_OTHER);
    }

    private function handleForm(Request $request, Item $item, string $title): Response
    {
        $form = $this->createForm(ItemType::class, $item)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $item->getId() === null;
            $this->em->persist($item);
            $this->em->flush();
            $this->addFlash('success', $isNew ? sprintf('« %s » a été ajouté.', $item->getName()) : 'Modifications enregistrées.');

            return $this->redirectToRoute('app_item_index', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('item/form.html.twig', ['form' => $form, 'title' => $title]);
    }
}
