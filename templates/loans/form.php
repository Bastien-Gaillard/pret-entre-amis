<?php
/** @var array<string, mixed> $loan */
/** @var array<string, string> $errors */
/** @var list<array<string, mixed>> $items */
/** @var list<array<string, mixed>> $friends */
$err = static fn (string $f): string => isset($errors[$f]) ? '<p class="field-error">' . e($errors[$f]) . '</p>' : '';
?>
<h1>Nouveau prêt</h1>

<?php if (!$items || !$friends): ?>
    <p class="empty">
        Pour prêter quelque chose, il faut au moins
        <?= !$items ? '<a href="/items/new">un objet disponible</a>' : '' ?>
        <?= !$items && !$friends ? 'et' : '' ?>
        <?= !$friends ? '<a href="/friends/new">un ami</a>' : '' ?>.
    </p>
<?php else: ?>
<form method="post" action="/loans" class="form" novalidate>
    <?= csrf_field() ?>

    <label>Objet *
        <select name="item_id" required>
            <option value="">— Choisir —</option>
            <?php $group = null; foreach ($items as $item): ?>
                <?php if ($item['category'] !== $group): ?>
                    <?= $group !== null ? '</optgroup>' : '' ?><optgroup label="<?= e($item['category']) ?>">
                    <?php $group = $item['category']; ?>
                <?php endif; ?>
                <option value="<?= e($item['id']) ?>"<?= (int) $loan['item_id'] === (int) $item['id'] ? ' selected' : '' ?>><?= e($item['name']) ?></option>
            <?php endforeach; ?>
            </optgroup>
        </select>
    </label>
    <?= $err('item_id') ?>

    <label>Prêté à *
        <select name="friend_id" required>
            <option value="">— Choisir —</option>
            <?php foreach ($friends as $friend): ?>
                <option value="<?= e($friend['id']) ?>"<?= (int) $loan['friend_id'] === (int) $friend['id'] ? ' selected' : '' ?>><?= e($friend['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?= $err('friend_id') ?>

    <div class="row">
        <label>Prêté le *
            <input type="date" name="loaned_at" value="<?= e($loan['loaned_at'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required>
        </label>
        <label>À rendre le
            <input type="date" name="due_at" value="<?= e($loan['due_at'] ?? '') ?>">
        </label>
    </div>
    <?= $err('loaned_at') ?><?= $err('due_at') ?>

    <label>Notes
        <input type="text" name="notes" value="<?= e($loan['notes'] ?? '') ?>" maxlength="255" placeholder="Pour quoi faire, état au départ…">
    </label>
    <?= $err('notes') ?>

    <div class="form-actions">
        <button class="btn btn-primary">Enregistrer le prêt</button>
        <a href="/" class="btn btn-ghost">Annuler</a>
    </div>
</form>
<?php endif; ?>
