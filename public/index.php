<?php

declare(strict_types=1);

use App\Controller\DashboardController;
use App\Controller\FriendController;
use App\Controller\ItemController;
use App\Controller\LoanController;
use App\Database;
use App\HttpException;
use App\Repository\FriendRepository;
use App\Repository\ItemRepository;
use App\Repository\LoanRepository;
use App\Router;
use App\View;

// Serveur intégré de PHP (php -S) : laisser passer les fichiers statiques.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
require __DIR__ . '/../src/helpers.php';

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

$view = new View(__DIR__ . '/../templates');

try {
    $pdo = Database::connection();
    $friendRepo = new FriendRepository($pdo);
    $itemRepo = new ItemRepository($pdo);
    $loanRepo = new LoanRepository($pdo);

    $dashboard = new DashboardController($view, $loanRepo);
    $friends = new FriendController($view, $friendRepo);
    $items = new ItemController($view, $itemRepo);
    $loans = new LoanController($view, $loanRepo, $itemRepo, $friendRepo);

    $router = new Router();
    $router->get('/', $dashboard->index(...));

    $router->get('/friends', $friends->index(...));
    $router->get('/friends/new', $friends->create(...));
    $router->post('/friends', $friends->store(...));
    $router->get('/friends/{id}/edit', $friends->edit(...));
    $router->post('/friends/{id}', $friends->update(...));
    $router->post('/friends/{id}/delete', $friends->delete(...));

    $router->get('/items', $items->index(...));
    $router->get('/items/new', $items->create(...));
    $router->post('/items', $items->store(...));
    $router->get('/items/{id}/edit', $items->edit(...));
    $router->post('/items/{id}', $items->update(...));
    $router->post('/items/{id}/delete', $items->delete(...));

    $router->get('/loans', $loans->history(...));
    $router->get('/loans/new', $loans->create(...));
    $router->post('/loans', $loans->store(...));
    $router->post('/loans/{id}/return', $loans->markReturned(...));

    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (HttpException $e) {
    $view->render('error', ['title' => $e->getMessage(), 'status' => $e->status], $e->status);
} catch (Throwable $e) {
    error_log((string) $e);
    $view->render('error', ['title' => 'Erreur serveur', 'status' => 500], 500);
}
