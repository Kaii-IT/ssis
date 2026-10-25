<h1>My payments</h1><p class="sub">Tuition assessments and document fees.</p>
<div class="card">
<?php if (!$rows): ?><p class="muted">No payments yet.</p><?php else: ?>
<table class="tbl"><tr><th>Ref</th><th>Description</th><th>Amount</th><th>Status</th><th>OR no.</th><th></th></tr>
<?php foreach ($rows as $p): ?>
  <tr><td>PAY-<?= (int)$p['id'] ?></td><td><?= e($p['description']) ?><?php if ($p['remarks']): ?><div class="muted">Cashier: <?= e($p['remarks']) ?></div><?php endif; ?></td>
      <td><?= money($p['amount']) ?></td><td><?= badge($p['status']) ?></td><td><?= e($p['or_number'] ?? '—') ?></td>
      <td class="right"><?php
        if (in_array($p['status'], ['unpaid', 'rejected'], true)) echo '<a class="btn sm" href="' . url("/student/payments/{$p['id']}/pay") . '">Pay</a>';
        elseif ($p['status'] === 'paid') echo '<a class="btn outline sm" target="_blank" href="' . url("/receipt/{$p['id']}") . '">Receipt</a>'; ?></td></tr>
<?php endforeach; ?></table><?php endif; ?>
</div>