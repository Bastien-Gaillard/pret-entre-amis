<?php
/** @var list<array<string, mixed>> $loans */
/** @var list<array<string, mixed>> $friends */
/** @var ?int $friendId */
/** @var ?string $status */
?>
<h1>Historique des prêts</h1>

<form method="get" action="/loans" class="filters-form">
    <select name="friend" onchange="this.form.submit()">
        <option value="">Tous les amis</option>
        <?php foreach ($friends as $friend): ?>
            <option value="<?= e($friend['id']) ?>"<?= $friendId === (int) $friend['id'] ? ' selected' : '' ?>><?= e($friend['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        <?php foreach (STATUS_LABELS as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-small">Filtrer</button></noscript>
</form>

<?php if (!$loans): ?>
    <p class="empty">Aucun prêt ne correspond.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Objet</th><th>Ami</th><th>Prêté le</th><th>Prévu le</th><th>Rendu le</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($loans as $loan): ?>
            <tr class="<?= $loan['status'] === 'en_retard' ? 'row-late' : '' ?>">
                <td>
                    <strong><?= e($loan['item_name']) ?></strong>
                    <?php if ($loan['notes']): ?><br><small class="muted"><?= e($loan['notes']) ?></small><?php endif; ?>
                </td>
                <td><?= e($loan['friend_name']) ?></td>
                <td><?= date_fr($loan['loaned_at']) ?></td>
                <td><?= date_fr($loan['due_at']) ?></td>
                <td><?= date_fr($loan['returned_at']) ?></td>
                <td><?= status_badge($loan['status']) ?></td>
                <td class="actions">
                    <?php if ($loan['returned_at'] === null): ?>
                        <form method="post" action="/loans/<?= e($loan['id']) ?>/return">
                            <?= csrf_field() ?>
                            <input type="hidden" name="back" value="history">
                            <button class="btn btn-small">Rendu ✓</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
