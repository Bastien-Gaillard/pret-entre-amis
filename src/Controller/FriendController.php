<?php

declare(strict_types=1);

namespace App\Controller;

use App\Csrf;
use App\Flash;
use App\HttpException;
use App\Input;
use App\Repository\FriendRepository;
use App\View;
use PDOException;

final class FriendController
{
    public function __construct(
        private readonly View $view,
        private readonly FriendRepository $friends,
    ) {
    }

    public function index(): void
    {
        $this->view->render('friends/index', [
            'title'   => 'Amis',
            'friends' => $this->friends->allWithStats(),
        ]);
    }

    public function create(): void
    {
        $this->renderForm(['name' => '', 'email' => '', 'phone' => '']);
    }

    public function store(): void
    {
        Csrf::verify();
        [$data, $errors] = $this->validate();
        if ($errors) {
            $this->renderForm($data, $errors);
            return;
        }

        try {
            $this->friends->create($data);
        } catch (PDOException $e) {
            $this->renderForm($data, $this->duplicateError($e));
            return;
        }
        Flash::add('success', sprintf('%s a été ajouté(e).', $data['name']));
        redirect('/friends');
    }

    public function edit(int $id): void
    {
        $this->renderForm($this->findOrFail($id), [], $id);
    }

    public function update(int $id): void
    {
        Csrf::verify();
        $this->findOrFail($id);
        [$data, $errors] = $this->validate();
        if ($errors) {
            $this->renderForm($data, $errors, $id);
            return;
        }

        try {
            $this->friends->update($id, $data);
        } catch (PDOException $e) {
            $this->renderForm($data, $this->duplicateError($e), $id);
            return;
        }
        Flash::add('success', 'Modifications enregistrées.');
        redirect('/friends');
    }

    public function delete(int $id): void
    {
        Csrf::verify();
        $friend = $this->findOrFail($id);
        try {
            $this->friends->delete($id);
            Flash::add('success', sprintf('%s a été supprimé(e).', $friend['name']));
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            Flash::add('error', sprintf('Impossible de supprimer %s : il ou elle a un historique de prêts.', $friend['name']));
        }
        redirect('/friends');
    }

    /** @return array{0: array{name: string, email: ?string, phone: ?string}, 1: array<string, string>} */
    private function validate(): array
    {
        $data = [
            'name'  => Input::string('name'),
            'email' => Input::nullable('email'),
            'phone' => Input::nullable('phone'),
        ];
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'Le nom est obligatoire.';
        } elseif (mb_strlen($data['name']) > 100) {
            $errors['name'] = '100 caractères maximum.';
        }
        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse e-mail invalide.';
        }
        if ($data['phone'] !== null && !preg_match('/^[0-9 +().-]{6,30}$/', $data['phone'])) {
            $errors['phone'] = 'Numéro de téléphone invalide.';
        }

        return [$data, $errors];
    }

    /** @return array<string, string> */
    private function duplicateError(PDOException $e): array
    {
        if ($e->getCode() !== '23000') {
            throw $e;
        }

        return ['name' => 'Un ami porte déjà ce nom.'];
    }

    private function findOrFail(int $id): array
    {
        return $this->friends->find($id) ?? throw new HttpException(404);
    }

    private function renderForm(array $friend, array $errors = [], ?int $id = null): void
    {
        $this->view->render('friends/form', [
            'title'  => $id ? 'Modifier un ami' : 'Ajouter un ami',
            'friend' => $friend,
            'errors' => $errors,
            'action' => $id ? "/friends/$id" : '/friends',
        ], $errors ? 422 : 200);
    }
}
