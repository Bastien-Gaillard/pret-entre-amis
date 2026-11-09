<?php

declare(strict_types=1);

namespace App\Controller;

use App\Csrf;
use App\Flash;
use App\HttpException;
use App\Input;
use App\Repository\FriendRepository;
use App\Repository\ItemRepository;
use App\Repository\LoanRepository;
use App\View;
use DomainException;

final class LoanController
{
    public function __construct(
        private readonly View $view,
        private readonly LoanRepository $loans,
        private readonly ItemRepository $items,
        private readonly FriendRepository $friends,
    ) {
    }

    public function history(): void
    {
        $friendId = Input::int('friend');
        $status = Input::nullable('status');
        if ($status !== null && !array_key_exists($status, STATUS_LABELS)) {
            $status = null;
        }

        $this->view->render('loans/history', [
            'title'    => 'Historique des prêts',
            'loans'    => $this->loans->history($friendId, $status),
            'friends'  => $this->friends->all(),
            'friendId' => $friendId,
            'status'   => $status,
        ]);
    }

    public function create(): void
    {
        $this->renderForm([
            'item_id'   => Input::int('item'),
            'friend_id' => Input::int('friend'),
            'loaned_at' => date('Y-m-d'),
            'due_at'    => date('Y-m-d', strtotime('+3 weeks')),
            'notes'     => '',
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'item_id'   => Input::int('item_id'),
            'friend_id' => Input::int('friend_id'),
            'loaned_at' => Input::date('loaned_at'),
            'due_at'    => Input::date('due_at'),
            'notes'     => Input::nullable('notes'),
        ];
        $errors = [];

        if ($data['item_id'] === null || $this->items->find($data['item_id']) === null) {
            $errors['item_id'] = 'Choisis un objet.';
        }
        if ($data['friend_id'] === null || $this->friends->find($data['friend_id']) === null) {
            $errors['friend_id'] = 'Choisis un ami.';
        }
        if ($data['loaned_at'] === null) {
            $errors['loaned_at'] = 'Date de prêt invalide.';
        } elseif ($data['loaned_at'] > date('Y-m-d')) {
            $errors['loaned_at'] = 'La date de prêt ne peut pas être dans le futur.';
        }
        if (Input::string('due_at') !== '' && $data['due_at'] === null) {
            $errors['due_at'] = 'Date de retour invalide.';
        } elseif ($data['due_at'] !== null && $data['loaned_at'] !== null && $data['due_at'] < $data['loaned_at']) {
            $errors['due_at'] = 'La date de retour doit être après la date de prêt.';
        }
        if ($data['notes'] !== null && mb_strlen($data['notes']) > 255) {
            $errors['notes'] = '255 caractères maximum.';
        }

        if (!$errors) {
            try {
                $this->loans->create($data['item_id'], $data['friend_id'], $data['loaned_at'], $data['due_at'], $data['notes']);
                Flash::add('success', 'Prêt enregistré.');
                redirect('/');
            } catch (DomainException $e) {
                $errors['item_id'] = $e->getMessage();
            }
        }

        $this->renderForm($data, $errors);
    }

    public function markReturned(int $id): void
    {
        Csrf::verify();
        $loan = $this->loans->find($id) ?? throw new HttpException(404);

        if ($this->loans->markReturned($id, date('Y-m-d'))) {
            Flash::add('success', sprintf('« %s » est revenu de chez %s.', $loan['item_name'], $loan['friend_name']));
        } else {
            Flash::add('error', 'Ce prêt était déjà marqué comme rendu.');
        }
        redirect(Input::string('back') === 'history' ? '/loans' : '/');
    }

    private function renderForm(array $loan, array $errors = []): void
    {
        $this->view->render('loans/form', [
            'title'   => 'Nouveau prêt',
            'loan'    => $loan,
            'errors'  => $errors,
            'items'   => $this->items->available(),
            'friends' => $this->friends->all(),
        ], $errors ? 422 : 200);
    }
}
