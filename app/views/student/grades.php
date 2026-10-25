<h1>My grades</h1><p class="sub">Grades appear once the Registrar encodes them.</p>
<?php if (!$groups): ?><div class="card"><p class="muted">No approved enrollment records yet.</p></div><?php endif; ?>
<?php foreach ($groups as $g): ?>
<div class="card">
  <h2><?= e($g['term']) ?> <?= $g['gwa'] !== null ? '· GWA ' . number_format($g['gwa'], 2) : '' ?></h2>
  <table class="tbl"><tr><th>Code</th><th>Subject</th><th>Units</th><th>Grade</th><th>Remarks</th></tr>
  <?php foreach ($g['rows'] as $r): ?>
    <tr><td><?= e($r['code']) ?></td><td><?= e($r['title']) ?></td><td><?= (int)$r['units'] ?></td>
        <td><?= $r['grade'] !== null ? e($r['grade']) : '<span class="muted">—</span>' ?></td>
        <td><?= $r['remarks'] ? badge($r['remarks']) : '' ?></td></tr>
  <?php endforeach; ?></table>
</div>
<?php endforeach; ?>