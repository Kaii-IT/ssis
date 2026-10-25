<h1>Document requests</h1><p class="sub">Move each paid request forward: Processing → Ready → Released.</p>
<div class="tabs"><?php foreach ($valid as $s): ?>
  <a class="<?= $st === $s ? 'on' : '' ?>" href="<?= url('/registrar/documents?status=' . $s) ?>"><?= ucwords(str_replace('_', ' ', $s)) ?></a><?php endforeach; ?></div>
<div class="card">
<?php if (!$rows): ?><p class="muted">No requests in this view.</p><?php else: ?>
<table class="tbl"><tr><th>ID</th><th>Student</th><th>Document</th><th>Status</th><th>Action</th></tr>
<?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['request_no']) ?></td><td><?= e($r['user_no']) ?><div class="muted"><?= e($r['last_name'] . ', ' . $r['first_name']) ?></div></td>
      <td><?= e($r['doc_name']) ?> × <?= (int)$r['copies'] ?><div class="muted"><?= e($r['purpose']) ?></div></td><td><?= badge($r['status']) ?></td>
      <td><?php
        $lbl = ['paid' => 'Start processing', 'processing' => 'Mark ready', 'ready' => 'Release'];
        if (isset($lbl[$r['status']])) echo post_btn("/registrar/documents/{$r['id']}/advance", $lbl[$r['status']]);
        elseif ($r['status'] === 'pending_payment') echo '<span class="muted">Awaiting student payment</span>';
        if (in_array($r['status'], ['paid', 'processing'], true)) echo remark_form("/registrar/documents/{$r['id']}/reject", 'Reject');
      ?></td></tr>
<?php endforeach; ?></table><?php endif; ?>
</div>