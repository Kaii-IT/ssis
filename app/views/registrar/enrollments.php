<h1>Enrollments</h1><p class="sub"><?= e($term['name']) ?></p>
<div class="tabs"><?php foreach (['submitted', 'approved', 'draft', 'all'] as $s): ?>
  <a class="<?= $st === $s ? 'on' : '' ?>" href="<?= url('/registrar/enrollments?status=' . $s) ?>"><?= ucfirst($s) ?></a><?php endforeach; ?></div>
<div class="card">
<?php if (!$rows): ?><p class="muted">Nothing here.</p><?php else: ?>
<table class="tbl"><tr><th>Student</th><th>Units</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['user_no']) ?> · <?= e($r['last_name'] . ', ' . $r['first_name']) ?></td><td><?= (int)$r['units'] ?></td><td><?= badge($r['status']) ?></td>
      <td class="right"><a class="btn outline sm" href="<?= url('/registrar/enrollments/' . $r['id']) ?>">Open</a></td></tr>
<?php endforeach; ?></table><?php endif; ?>
</div>