<?php

declare(strict_types=1);

namespace App\Controller;

use App\Csrf;
use App\Flash;
use App\HttpException;
use App\Input;
use App\Repository\ItemRepository;
use App\View;
use PDOException;

final class ItemController
{
    public const DEFAULT_CATEGORIES = ['Bricolage', 'Camping', 'Cuisine', 'Jeu', 'Livre', 'Sport', 'Autre'];

    public function __construct(
        private readonly View $view,
        private readonly ItemRepository $items,
    ) {
    }

    public function index(): void
    {
        $category = Input::nullable('category');
        $this->view->render('items/index', [
            'title'      => 'Objets',
            'items'      => $this->items->allWithCurrentLoan($category),
            'categories' => $this->items->categories(),
            'current'    => $category,
        ]);
    }

    public function create(): void
    {
        $this->renderForm(['name' => '', 'category' => 'Autre', 'notes' => '']);
    }

    public function store(): void
    {
        Csrf::verify();
        [$data, $errors] = $this->validate();
        if ($errors) {
            $this->renderForm($data, $errors);
            return;
        }
        $this->items->create($data);
        Flash::add('success', sprintf('« %s » a été ajouté.', $data['name']));
        redirect('/items');
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
        $this->items->update($id, $data);
        Flash::add('success', 'Modifications enregistrées.');
        redirect('/items');
    }

    public function delete(int $id): void
    {
        Csrf::verify();
        $item = $this->findOrFail($id);
        try {
            $this->items->delete($id);
            Flash::add('success', sprintf('« %s » a été supprimé.', $item['name']));
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            Flash::add('error', sprintf('Impossible de supprimer « %s » : il a un historique de prêts.', $item['name']));
        }
        redirect('/items');
    }

    /** @return array{0: array{name: string, category: string, notes: ?string}, 1: array<string, string>} */
    private function validate(): array
    {
        $data = [
            'name'     => Input::string('name'),
            'category' => Input::string('category') ?: 'Autre',
            'notes'    => Input::nullable('notes'),
        ];
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'Le nom est obligatoire.';
        } elseif (mb_strlen($data['name']) > 150) {
            $errors['name'] = '150 caractères maximum.';
        }
        if (mb_strlen($data['category']) > 50) {
            $errors['category'] = '50 caractères maximum.';
        }

        return [$data, $errors];
    }

    private function findOrFail(int $id): array
    {
        return $this->items->find($id) ?? throw new HttpException(404);
    }

    private function renderForm(array $item, array $errors = [], ?int $id = null): void
    {
        $categories = array_values(array_unique([...self::DEFAULT_CATEGORIES, ...$this->items->categories()]));
        sort($categories);

        $this->view->render('items/form', [
            'title'      => $id ? 'Modifier un objet' : 'Ajouter un objet',
            'item'       => $item,
            'errors'     => $errors,
            'categories' => $categories,
            'action'     => $id ? "/items/$id" : '/items',
        ], $errors ? 422 : 200);
    }
}
