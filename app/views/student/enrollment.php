<h1>Course Enrollment</h1>
<p class="sub"><?= e($term['name']) ?> · Unit limit <?= (int)$max ?></p>
<?php $status = $enr['status'] ?? 'draft'; $editable = $status === 'draft'; ?>
<div class="card">
  <div class="row"><h2 style="margin:0">Your selection (<?= (int)$units ?> units)</h2><?= badge($status) ?></div>
  <?php if (!empty($enr['remarks'])): ?><div class="alert error" style="margin-top:10px">Returned by Registrar: <?= e($enr['remarks']) ?></div><?php endif; ?>
  <?php if (!$items): ?><p class="muted">No subjects selected yet.</p><?php else: ?>
  <table class="tbl"><tr><th>Code</th><th>Subject</th><th>Sec</th><th>Schedule</th><th>Units</th><th></th></tr>
  <?php foreach ($items as $i): ?>
    <tr><td><?= e($i['code']) ?></td><td><?= e($i['title']) ?></td><td><?= e($i['section']) ?></td>
        <td><?= e($i['schedule']) ?> · <?= e($i['room']) ?></td><td><?= (int)$i['units'] ?></td>
        <td class="right"><?= $editable ? post_btn("/student/enrollment/drop/{$i['id']}", 'Drop', 'btn outline sm', [], 'Drop this subject?') : '' ?></td></tr>
  <?php endforeach; ?></table>
  <?php if ($editable): ?><p><?= post_btn('/student/enrollment/submit', 'Submit for approval', 'btn', [], 'Submit enrollment? You cannot edit it afterwards.') ?></p><?php endif; ?>
  <?php endif; ?>
  <?php if ($status === 'submitted'): ?><p class="muted">Waiting for Registrar approval.</p><?php endif; ?>
  <?php if ($status === 'approved'): ?><p class="muted">Approved. Check Payments for your tuition assessment.</p><?php endif; ?>
</div>
<div class="card">
  <h2>Available subjects</h2>
  <table class="tbl"><tr><th>Code</th><th>Subject</th><th>Sec</th><th>Schedule</th><th>Slots</th><th>Status</th><th></th></tr>
  <?php foreach ($offers as $o): $left = max(0, $o['slots'] - $o['taken']); ?>
    <tr><td><?= e($o['code']) ?></td><td><?= e($o['title']) ?> <span class="muted">(<?= (int)$o['units'] ?>u)</span></td><td><?= e($o['section']) ?></td>
        <td><?= e($o['schedule']) ?></td><td><?= $left ?></td>
        <td><?= $o['mine'] ? badge('approved') : ($left ? '<span class="badge ok">Open</span>' : '<span class="badge bad">Full</span>') ?></td>
        <td class="right"><?php if ($editable && !$o['mine']): ?>
          <?= $left ? post_btn("/student/enrollment/add/{$o['id']}", 'Enroll') : '<button class="btn sm" disabled>Enroll</button>' ?><?php endif; ?></td></tr>
  <?php endforeach; ?></table>
  <p class="hint">The system refuses full classes, duplicate subjects, already-passed subjects and unit-limit overruns.</p>
</div>