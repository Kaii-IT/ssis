<h1>Student Dashboard</h1>
<p class="sub"><?= e($s['first_name'] . ' ' . $s['last_name']) ?> · <?= e($s['dept_name']) ?> · <?= e($term['name']) ?></p>
<div class="stats">
  <div class="card stat"><b><?= $units ?></b><span>Enrolled units</span></div>
  <div class="card stat"><b><?= $active ?></b><span>Active requests</span></div>
  <div class="card stat"><b><?= $unpaid ?></b><span>Awaiting payment</span></div>
  <div class="card stat"><b><?= $cleared ?>/<?= $ctotal ?></b><span>Clearances cleared</span></div>
</div>
<div class="card">
  <h2>Quick actions</h2>
  <p class="muted">Enrollment status: <?= $enr ? badge($enr['status']) : badge('draft') . ' not started' ?></p>
  <div class="row">
    <a class="btn" href="<?= url('/student/enrollment') ?>">Enroll in subjects</a>
    <a class="btn outline" href="<?= url('/student/documents/new') ?>">Request a document</a>
    <a class="btn outline" href="<?= url('/student/clearance') ?>">Clearance</a>
    <a class="btn outline" href="<?= url('/student/grades') ?>">View grades</a>
  </div>
</div>
<div class="card">
  <h2>Recent requests</h2>
  <?php if (!$recent): ?><p class="muted">No document requests yet.</p><?php else: ?>
  <table class="tbl"><tr><th>ID</th><th>Document</th><th>Status</th></tr>
  <?php foreach ($recent as $r): ?>
    <tr><td><?= e($r['request_no']) ?></td>
        <td><?= e($r['name']) ?> × <?= (int)$r['copies'] ?><div class="muted"><?= e($r['purpose']) ?></div><?= pipeline($r['status']) ?></td>
        <td><?= badge($r['status']) ?></td></tr>
  <?php endforeach; ?></table><?php endif; ?>
</div>