<?php
/** @var list<array<string, mixed>> $activeLoans */
/** @var array{active: int, overdue: int, total: int, returned: int} $stats */
/** @var list<array<string, mixed>> $topBorrowers */
?>
<h1>Tableau de bord</h1>

<section class="stats">
    <div class="stat"><span class="stat-value"><?= e($stats['active']) ?></span><span class="stat-label">prêts en cours</span></div>
    <div class="stat stat-warn"><span class="stat-value"><?= e($stats['overdue']) ?></span><span class="stat-label">en retard</span></div>
    <div class="stat"><span class="stat-value"><?= e($stats['returned']) ?></span><span class="stat-label">objets rendus</span></div>
    <div class="stat"><span class="stat-value"><?= e($stats['total']) ?></span><span class="stat-label">prêts au total</span></div>
</section>

<section>
    <h2>Chez qui sont mes affaires ?</h2>
    <?php if (!$activeLoans): ?>
        <p class="empty">Rien n'est prêté en ce moment. Tout est à la maison 🎉</p>
    <?php else: ?>
        <table>
            <thead>
            <tr><th>Objet</th><th>Chez</th><th>Prêté le</th><th>À rendre le</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($activeLoans as $loan): ?>
                <tr class="<?= $loan['status'] === 'en_retard' ? 'row-late' : '' ?>">
                    <td><strong><?= e($loan['item_name']) ?></strong> <small class="muted"><?= e($loan['category']) ?></small></td>
                    <td><a href="/loans?friend=<?= e($loan['friend_id']) ?>"><?= e($loan['friend_name']) ?></a></td>
                    <td><?= date_fr($loan['loaned_at']) ?></td>
                    <td>
                        <?= date_fr($loan['due_at']) ?>
                        <?php if ($loan['due_at'] !== null): ?>
                            <?php $days = days_from_today($loan['due_at']); ?>
                            <small class="muted">
                                <?= $days < 0 ? e(-$days) . ' j de retard' : ($days === 0 ? "aujourd'hui" : 'dans ' . e($days) . ' j') ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td><?= status_badge($loan['status']) ?></td>
                    <td class="actions">
                        <form method="post" action="/loans/<?= e($loan['id']) ?>/return">
                            <?= csrf_field() ?>
                            <button class="btn btn-small">Rendu ✓</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php if ($topBorrowers): ?>
<section>
    <h2>Les plus gros emprunteurs</h2>
    <table class="compact">
        <thead><tr><th>Ami</th><th>Prêts</th><th>Rendus à l'heure</th></tr></thead>
        <tbody>
        <?php foreach ($topBorrowers as $b): ?>
            <tr>
                <td><a href="/loans?friend=<?= e($b['id']) ?>"><?= e($b['name']) ?></a></td>
                <td><?= e($b['loans']) ?></td>
                <td><?= (int) $b['returned'] > 0 ? e($b['on_time']) . ' / ' . e($b['returned']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>
