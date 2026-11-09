<?php

namespace App\Controller;

use App\Entity\Friend;
use App\Form\FriendType;
use App\Repository\FriendRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/amis')]
final class FriendController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'app_friend_index', methods: ['GET'])]
    public function index(FriendRepository $friends): Response
    {
        return $this->render('friend/index.html.twig', ['rows' => $friends->findAllWithStats()]);
    }

    #[Route('/nouveau', name: 'app_friend_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new Friend(), 'Ajouter un ami');
    }

    #[Route('/{id}/modifier', name: 'app_friend_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Friend $friend): Response
    {
        return $this->handleForm($request, $friend, 'Modifier ' . $friend->getName());
    }

    #[Route('/{id}/supprimer', name: 'app_friend_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Friend $friend): Response
    {
        if (!$this->isCsrfTokenValid('delete-friend-' . $friend->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$friend->getLoans()->isEmpty()) {
            $this->addFlash('error', sprintf('Impossible de supprimer %s : il ou elle a un historique de prêts.', $friend->getName()));
        } else {
            $this->em->remove($friend);
            $this->em->flush();
            $this->addFlash('success', sprintf('%s a été supprimé(e).', $friend->getName()));
        }

        return $this->redirectToRoute('app_friend_index', status: Response::HTTP_SEE_OTHER);
    }

    private function handleForm(Request $request, Friend $friend, string $title): Response
    {
        $form = $this->createForm(FriendType::class, $friend)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $friend->getId() === null;
            $this->em->persist($friend);
            $this->em->flush();
            $this->addFlash('success', $isNew ? sprintf('%s a été ajouté(e).', $friend->getName()) : 'Modifications enregistrées.');

            return $this->redirectToRoute('app_friend_index', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('friend/form.html.twig', ['form' => $form, 'title' => $title, 'friend' => $friend]);
    }
}
