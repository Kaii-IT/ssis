<h1>My Requests</h1><p class="sub">Track every document request and pay pending fees.</p>
<p><a class="btn" href="<?= url('/student/documents/new') ?>">New request</a></p>
<div class="card">
<?php if (!$rows): ?><p class="muted">No requests yet.</p><?php else: ?>
<table class="tbl"><tr><th>ID</th><th>Document</th><th>Status</th><th>Fee</th><th></th></tr>
<?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['request_no']) ?></td>
      <td><?= e($r['doc_name']) ?> × <?= (int)$r['copies'] ?><div class="muted"><?= e($r['purpose']) ?></div><?= pipeline($r['status']) ?>
          <?php if ($r['remarks']): ?><div class="muted">Note: <?= e($r['remarks']) ?></div><?php endif; ?></td>
      <td><?= badge($r['status']) ?></td><td><?= money($r['amount']) ?></td>
      <td class="right">
        <?php if (in_array($r['pay_status'], ['unpaid', 'rejected'], true)) echo '<a class="btn sm" href="' . url("/student/payments/{$r['payment_id']}/pay") . '">Pay</a> '; ?>
        <?php if ($r['status'] === 'pending_payment') echo post_btn("/student/documents/{$r['id']}/cancel", 'Cancel', 'btn outline sm', [], 'Cancel this request?'); ?>
      </td></tr>
<?php endforeach; ?></table><?php endif; ?>
</div>