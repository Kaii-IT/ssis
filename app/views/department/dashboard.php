<h1><?= e($d['name']) ?></h1><p class="sub">Clearance processing · <?= e($term['name']) ?></p>
<div class="stats">
  <div class="card stat"><b><?= (int)($c['pending'] ?? 0) ?></b><span>Pending</span></div>
  <div class="card stat"><b><?= (int)($c['cleared'] ?? 0) ?></b><span>Cleared</span></div>
  <div class="card stat"><b><?= (int)($c['hold'] ?? 0) ?></b><span>On hold</span></div>
</div>
<div class="card"><a class="btn" href="<?= url('/department/clearance') ?>">Process clearances</a></div>