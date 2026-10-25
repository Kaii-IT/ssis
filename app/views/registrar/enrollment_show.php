<h1>Enrollment review</h1>
<p class="sub"><?= e($e['user_no'] . ' · ' . $e['first_name'] . ' ' . $e['last_name']) ?> · <?= e($e['program']) ?> Y<?= (int)$e['year_level'] ?> · <?= e($e['term']) ?> <?= badge($e['status']) ?></p>
<div class="card">
  <table class="tbl"><tr><th>Code</th><th>Subject</th><th>Section</th><th>Schedule</th><th>Units</th></tr>
  <?php foreach ($items as $i): ?>
    <tr><td><?= e($i['code']) ?></td><td><?= e($i['title']) ?></td><td><?= e($i['section']) ?></td><td><?= e($i['schedule']) ?> · <?= e($i['room']) ?></td><td><?= (int)$i['units'] ?></td></tr>
  <?php endforeach; ?></table>
  <p><strong><?= (int)$units ?> units</strong> · Assessed tuition &amp; fees: <strong><?= money($fee) ?></strong></p>
  <?php if ($e['status'] === 'submitted'): ?>
    <div class="row">
      <?= post_btn("/registrar/enrollments/{$e['id']}/approve", 'Approve & assess', 'btn', [], 'Approve this enrollment?') ?>
      <?= remark_form("/registrar/enrollments/{$e['id']}/return", 'Return for revision') ?>
    </div>
  <?php endif; ?>
  <p><a href="<?= url('/registrar/enrollments') ?>">← Back</a></p>
</div>