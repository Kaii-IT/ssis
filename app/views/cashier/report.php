<h1>Daily collection report</h1>
<form method="get" class="row" style="margin-bottom:12px"><input type="date" name="date" value="<?= e($date) ?>" style="max-width:200px"><button class="btn outline sm">View</button></form>
<div class="stats">
  <div class="card stat"><b><?= money($total) ?></b><span>Total collected</span></div>
  <div class="card stat"><b><?= count($rows) ?></b><span>Official receipts</span></div>
  <?php foreach ($by as $m => $amt): ?><div class="card stat"><b><?= money($amt) ?></b><span><?= e(ucfirst($m)) ?></span></div><?php endforeach; ?>
</div>
<div class="card">
<?php if (!$rows): ?><p class="muted">No collections on this date.</p><?php else: ?>
<table class="tbl"><tr><th>OR no.</th><th>Student</th><th>Description</th><th>Method</th><th class="right">Amount</th></tr>
<?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['or_number']) ?></td><td><?= e($r['user_no'] . ' · ' . $r['last_name']) ?></td><td><?= e($r['description']) ?></td><td><?= e($r['method']) ?></td><td class="right"><?= money($r['amount']) ?></td></tr>
<?php endforeach; ?></table><?php endif; ?>
</div>