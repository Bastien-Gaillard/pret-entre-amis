<?php
/** @var string $title */
/** @var array<string, mixed> $item */
/** @var array<string, string> $errors */
/** @var list<string> $categories */
/** @var string $action */
?>
<h1><?= e($title) ?></h1>

<form method="post" action="<?= e($action) ?>" class="form" novalidate>
    <?= csrf_field() ?>

    <label>Nom *
        <input type="text" name="name" value="<?= e($item['name']) ?>" maxlength="150" required autofocus
               <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
    </label>
    <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>

    <label>Catégorie
        <input type="text" name="category" value="<?= e($item['category']) ?>" maxlength="50" list="categories">
        <datalist id="categories">
            <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"><?php endforeach; ?>
        </datalist>
    </label>
    <?php if (isset($errors['category'])): ?><p class="field-error"><?= e($errors['category']) ?></p><?php endif; ?>

    <label>Notes
        <textarea name="notes" rows="3" placeholder="État, accessoires fournis…"><?= e($item['notes'] ?? '') ?></textarea>
    </label>

    <div class="form-actions">
        <button class="btn btn-primary">Enregistrer</button>
        <a href="/items" class="btn btn-ghost">Annuler</a>
    </div>
</form>
