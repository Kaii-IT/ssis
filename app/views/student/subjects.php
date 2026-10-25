<h1>My subjects</h1><p class="sub"><?= e($term['name']) ?></p>
<div class="card">
<?php if (!$rows): ?>
  <p class="muted">No approved enrollment yet<?= $enr ? ' (status: ' . e($enr['status']) . ')' : '' ?>. <a href="<?= url('/student/enrollment') ?>">Go to enrollment</a>.</p>
<?php else: ?>
  <table class="tbl"><tr><th>Code</th><th>Subject</th><th>Section</th><th>Schedule</th><th>Room</th><th>Units</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['code']) ?></td><td><?= e($r['title']) ?></td><td><?= e($r['section']) ?></td><td><?= e($r['schedule']) ?></td><td><?= e($r['room']) ?></td><td><?= (int)$r['units'] ?></td></tr>
  <?php endforeach; ?></table>
<?php endif; ?>
</div>