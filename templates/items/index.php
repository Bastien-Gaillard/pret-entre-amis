<?php
/** @var list<array<string, mixed>> $items */
/** @var list<string> $categories */
/** @var ?string $current */
?>
<div class="page-head">
    <h1>Objets</h1>
    <a href="/items/new" class="btn">+ Ajouter un objet</a>
</div>

<?php if ($categories): ?>
<nav class="filters" aria-label="Catégories">
    <a href="/items"<?= $current === null ? ' aria-current="page"' : '' ?>>Tous</a>
    <?php foreach ($categories as $category): ?>
        <a href="/items?category=<?= e(urlencode($category)) ?>"<?= $current === $category ? ' aria-current="page"' : '' ?>><?= e($category) ?></a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>

<?php if (!$items): ?>
    <p class="empty">Aucun objet ici.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Objet</th><th>Catégorie</th><th>Où est-il ?</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <strong><?= e($item['name']) ?></strong>
                    <?php if ($item['notes']): ?><br><small class="muted"><?= e($item['notes']) ?></small><?php endif; ?>
                </td>
                <td><?= e($item['category']) ?></td>
                <td>
                    <?php if ($item['loan_id']): ?>
                        Chez <a href="/loans?friend=<?= e($item['borrower_id']) ?>"><?= e($item['borrower_name']) ?></a>
                        <?php if ($item['due_at'] && $item['due_at'] < date('Y-m-d')): ?><?= status_badge('en_retard') ?><?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-rendu">À la maison</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <?php if (!$item['loan_id']): ?>
                        <a href="/loans/new?item=<?= e($item['id']) ?>" class="btn btn-small">Prêter</a>
                    <?php endif; ?>
                    <a href="/items/<?= e($item['id']) ?>/edit" class="btn btn-small btn-ghost">Modifier</a>
                    <form method="post" action="/items/<?= e($item['id']) ?>/delete" onsubmit="return confirm('Supprimer cet objet ?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-small btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
