<h1>Clearance — <?= e($d['name']) ?></h1><p class="sub">Clear a student or place a hold with a reason.</p>
<div class="tabs"><?php foreach (['pending', 'cleared', 'hold', 'all'] as $s): ?>
  <a class="<?= $st === $s ? 'on' : '' ?>" href="<?= url('/department/clearance?status=' . $s) ?>"><?= ucfirst($s) ?></a><?php endforeach; ?></div>
<div class="card">
<?php if (!$rows): ?><p class="muted">No clearance requests in this view.</p><?php else: ?>
<table class="tbl"><tr><th>Student</th><th>Program</th><th>Status</th><th>Action</th></tr>
<?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['user_no']) ?><div class="muted"><?= e($r['last_name'] . ', ' . $r['first_name']) ?></div></td>
      <td><?= e($r['program']) ?> · Y<?= (int)$r['year_level'] ?></td>
      <td><?= badge($r['status']) ?><div class="muted"><?= e($r['remarks']) ?></div></td>
      <td><?php if ($r['status'] !== 'cleared') echo post_btn("/department/clearance/{$r['id']}/clear", 'Clear');
                if ($r['status'] !== 'hold') echo remark_form("/department/clearance/{$r['id']}/hold", 'Place hold'); ?></td></tr>
<?php endforeach; ?></table><?php endif; ?>
</div>