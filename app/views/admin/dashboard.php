<h1>Admin Dashboard</h1><p class="sub">Accounts and system activity</p>
<div class="stats">
  <?php foreach (['student', 'registrar', 'cashier', 'department', 'admin'] as $r): ?>
    <div class="card stat"><b><?= (int)($byRole[$r] ?? 0) ?></b><span><?= ucfirst($r) ?> accounts</span></div><?php endforeach; ?>
  <div class="card stat"><b><?= (int)$active ?></b><span>Active users</span></div>
  <div class="card stat"><b><?= (int)$locked ?></b><span>Locked accounts</span></div>
</div>
<div class="card row"><a class="btn" href="<?= url('/admin/users/new') ?>">Create account</a><a class="btn outline" href="<?= url('/admin/users') ?>">Manage users</a><a class="btn outline" href="<?= url('/admin/settings') ?>">Settings</a></div>
<div class="card"><h2>Recent activity</h2>
  <table class="tbl"><?php foreach ($logs as $l): ?>
    <tr><td class="muted"><?= e($l['created_at']) ?></td><td><?= e($l['user_no'] ?? '—') ?></td><td><?= e($l['action']) ?></td><td class="muted"><?= e($l['details']) ?></td></tr>
  <?php endforeach; ?></table></div>