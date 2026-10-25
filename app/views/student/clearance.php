<h1>Clearance</h1><p class="sub"><?= e($term['name']) ?> · every office must clear you before a Transcript of Records can be requested.</p>
<div class="card">
<?php if (!$items): ?>
  <p class="muted">You have not started clearance for this term.</p>
  <?= post_btn('/student/clearance/start', 'Start clearance', 'btn') ?>
<?php else: ?>
  <table class="tbl"><tr><th>Office</th><th>Status</th><th>Remarks</th><th>Updated</th></tr>
  <?php foreach ($items as $i): ?>
    <tr><td><?= e($i['dept']) ?></td><td><?= badge($i['status']) ?></td><td><?= e($i['remarks']) ?></td><td class="muted"><?= e($i['processed_at']) ?></td></tr>
  <?php endforeach; ?></table>
<?php endif; ?>
</div>