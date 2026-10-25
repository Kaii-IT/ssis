<div class="receipt">
  <h1>CuyoTech University</h1>
  <p class="sub">Official Receipt · <?= e($p['or_number']) ?></p>
  <dl>
    <dt>Date paid</dt><dd><?= e($p['paid_at']) ?></dd>
    <dt>Received from</dt><dd><?= e($p['first_name'] . ' ' . $p['last_name']) ?> (<?= e($p['user_no']) ?>)</dd>
    <dt>Particulars</dt><dd><?= e($p['description']) ?></dd>
    <dt>Method</dt><dd><?= e(ucfirst($p['method'])) ?><?= $p['reference_no'] ? ' · Ref ' . e($p['reference_no']) : '' ?></dd>
    <dt>Received by</dt><dd><?= e(trim(($p['c_first'] ?? '') . ' ' . ($p['c_last'] ?? ''))) ?></dd>
  </dl>
  <p class="big"><?= money($p['amount']) ?></p>
  <p class="noprint"><button class="btn" onclick="window.print()">Print</button></p>
</div>