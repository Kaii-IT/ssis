<h1>Registrar Dashboard</h1><p class="sub"><?= e($term['name']) ?></p>
<div class="stats">
  <div class="card stat"><b><?= (int)$c['submitted'] ?></b><span>Enrollments to review</span></div>
  <div class="card stat"><b><?= (int)$c['paid'] ?></b><span>Paid, awaiting processing</span></div>
  <div class="card stat"><b><?= (int)$c['processing'] ?></b><span>In progress</span></div>
  <div class="card stat"><b><?= (int)$c['ready'] ?></b><span>Ready for release</span></div>
</div>
<div class="card row">
  <a class="btn" href="<?= url('/registrar/enrollments') ?>">Review enrollments</a>
  <a class="btn outline" href="<?= url('/registrar/documents') ?>">Document requests</a>
  <a class="btn outline" href="<?= url('/registrar/grades') ?>">Encode grades</a>
</div>