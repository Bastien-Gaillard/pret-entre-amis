<?php /** @var list<array<string, mixed>> $friends */ ?>
<div class="page-head">
    <h1>Amis</h1>
    <a href="/friends/new" class="btn">+ Ajouter un ami</a>
</div>

<?php if (!$friends): ?>
    <p class="empty">Aucun ami enregistré pour l'instant.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Nom</th><th>Contact</th><th>En cours</th><th>Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($friends as $friend): ?>
            <tr>
                <td><a href="/loans?friend=<?= e($friend['id']) ?>"><strong><?= e($friend['name']) ?></strong></a></td>
                <td class="muted">
                    <?= $friend['email'] ? '<a href="mailto:' . e($friend['email']) . '">' . e($friend['email']) . '</a>' : '' ?>
                    <?= $friend['email'] && $friend['phone'] ? '·' : '' ?>
                    <?= e($friend['phone'] ?? '') ?>
                </td>
                <td><?= (int) $friend['active_loans'] > 0 ? '<span class="badge badge-en_cours">' . e($friend['active_loans']) . '</span>' : '0' ?></td>
                <td><?= e($friend['total_loans']) ?></td>
                <td class="actions">
                    <a href="/loans/new?friend=<?= e($friend['id']) ?>" class="btn btn-small">Prêter</a>
                    <a href="/friends/<?= e($friend['id']) ?>/edit" class="btn btn-small btn-ghost">Modifier</a>
                    <form method="post" action="/friends/<?= e($friend['id']) ?>/delete" onsubmit="return confirm(<?= e(json_encode('Supprimer ' . $friend['name'] . ' ?')) ?>)">
                        <?= csrf_field() ?>
                        <button class="btn btn-small btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
