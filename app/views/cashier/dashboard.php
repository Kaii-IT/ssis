<h1>Cashier Dashboard</h1><p class="sub">Overview of pending work</p>
<div class="stats">
  <div class="card stat"><b><?= (int)$c['verify'] ?></b><span>Payments to verify</span></div>
  <div class="card stat"><b><?= (int)$c['unpaid'] ?></b><span>Unpaid / resubmit</span></div>
  <div class="card stat"><b><?= (int)$c['count'] ?></b><span>Receipts issued today</span></div>
  <div class="card stat"><b><?= money($c['today']) ?></b><span>Collected today</span></div>
</div>
<div class="card"><a class="btn" href="<?= url('/cashier/payments') ?>">Open payments</a></div>