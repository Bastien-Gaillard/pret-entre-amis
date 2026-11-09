<?php
/** @var string $title */
/** @var string $content */
/** @var list<array{type: string, message: string}> $flashes */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$nav = ['/' => 'Tableau de bord', '/items' => 'Objets', '/friends' => 'Amis', '/loans' => 'Historique'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Prêts entre amis</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="topbar">
    <a href="/" class="brand">📦 Prêts entre amis</a>
    <nav>
        <?php foreach ($nav as $href => $label): ?>
            <?php $active = $path === $href || (in_array($href, ['/items', '/friends'], true) && str_starts_with($path, $href . '/')); ?>
            <a href="<?= e($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <a href="/loans/new" class="btn btn-primary">+ Nouveau prêt</a>
</header>

<main>
    <?php foreach ($flashes as $flash): ?>
        <p class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endforeach; ?>

    <?= $content ?>
</main>
</body>
</html>
