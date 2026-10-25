<h1>Audit log</h1><p class="sub">Every sensitive action is recorded with user, time and IP.</p>
<form method="get" class="row" style="margin-bottom:12px"><input name="q" value="<?= e($q) ?>" placeholder="Action or user ID" style="max-width:260px"><button class="btn outline sm">Search</button></form>
<div class="card"><table class="tbl"><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr>
<?php foreach ($rows as $l): ?>
  <tr><td class="muted"><?= e($l['created_at']) ?></td><td><?= e($l['user_no'] ?? '—') ?></td><td><?= e($l['action']) ?></td>
      <td><?= e($l['entity']) ?> <?= e($l['entity_id']) ?></td><td class="muted"><?= e($l['details']) ?></td><td class="muted"><?= e($l['ip']) ?></td></tr>
<?php endforeach; ?></table>
<p class="row">Page <?= (int)$page ?> of <?= (int)$pages ?>
  <?php if ($page > 1): ?><a href="<?= url('/admin/audit?page=' . ($page - 1) . '&q=' . urlencode($q)) ?>">← Prev</a><?php endif; ?>
  <?php if ($page < $pages): ?><a href="<?= url('/admin/audit?page=' . ($page + 1) . '&q=' . urlencode($q)) ?>">Next →</a><?php endif; ?></p></div>