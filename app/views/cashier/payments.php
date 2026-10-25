<h1>Payments</h1><p class="sub">Verify submitted payments, record cash, and issue official receipts.</p>
<div class="tabs"><?php foreach (['for_verification', 'unpaid', 'rejected', 'paid', 'void', 'all'] as $s): ?>
  <a class="<?= $st === $s ? 'on' : '' ?>" href="<?= url('/cashier/payments?status=' . $s) ?>"><?= ucwords(str_replace('_', ' ', $s)) ?></a><?php endforeach; ?></div>
<form method="get" class="row" style="margin-bottom:12px"><input type="hidden" name="status" value="<?= e($st) ?>">
  <input name="q" value="<?= e($q) ?>" placeholder="Student no., last name or reference" style="max-width:320px"><button class="btn outline sm">Search</button></form>
<div class="card">
<?php if (!$rows): ?><p class="muted">No payments in this view.</p><?php else: ?>
<table class="tbl"><tr><th>Ref</th><th>Student</th><th>Description</th><th>Amount</th><th>Method / Ref</th><th>Status</th><th>Action</th></tr>
<?php foreach ($rows as $p): ?>
  <tr><td>PAY-<?= (int)$p['id'] ?></td><td><?= e($p['user_no']) ?><div class="muted"><?= e($p['last_name'] . ', ' . $p['first_name']) ?></div></td>
      <td><?= e($p['description']) ?></td><td><?= money($p['amount']) ?></td>
      <td><?= e($p['method'] ?? '—') ?><div class="muted"><?= e($p['reference_no'] ?? '') ?></div></td><td><?= badge($p['status']) ?></td>
      <td><?php
        if ($p['status'] === 'for_verification') { echo post_btn("/cashier/payments/{$p['id']}/verify", 'Verify & issue OR', 'btn sm', [], 'Confirm the reference number was received?'); echo remark_form("/cashier/payments/{$p['id']}/reject", 'Reject'); }
        elseif (in_array($p['status'], ['unpaid', 'rejected'], true)) echo post_btn("/cashier/payments/{$p['id']}/cash", 'Accept cash', 'btn sm', [], 'Record cash received for ' . $p['description'] . '?');
        elseif ($p['status'] === 'paid') echo '<a class="btn outline sm" target="_blank" href="' . url("/receipt/{$p['id']}") . '">Receipt</a>';
      ?></td></tr>
<?php endforeach; ?></table><?php endif; ?>
</div>