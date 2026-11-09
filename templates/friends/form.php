<?php
/** @var string $title */
/** @var array<string, mixed> $friend */
/** @var array<string, string> $errors */
/** @var string $action */
?>
<h1><?= e($title) ?></h1>

<form method="post" action="<?= e($action) ?>" class="form" novalidate>
    <?= csrf_field() ?>

    <label>Nom *
        <input type="text" name="name" value="<?= e($friend['name']) ?>" maxlength="100" required autofocus
               <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
    </label>
    <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>

    <label>E-mail
        <input type="email" name="email" value="<?= e($friend['email'] ?? '') ?>" maxlength="255"
               <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
    </label>
    <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>

    <label>Téléphone
        <input type="tel" name="phone" value="<?= e($friend['phone'] ?? '') ?>" maxlength="30"
               <?= isset($errors['phone']) ? 'aria-invalid="true"' : '' ?>>
    </label>
    <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>

    <div class="form-actions">
        <button class="btn btn-primary">Enregistrer</button>
        <a href="/friends" class="btn btn-ghost">Annuler</a>
    </div>
</form>
