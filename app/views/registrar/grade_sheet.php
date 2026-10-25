<h1>Grade sheet</h1><p class="sub"><?= e($o['code'] . ' · ' . $o['title']) ?> · Section <?= e($o['section']) ?></p>
<div class="card">
<?php if (!$rows): ?><p class="muted">No students with an approved enrollment in this class.</p><?php else: ?>
<form method="post" action="<?= url('/registrar/grades/' . $o['id']) ?>"><?= csrf_field() ?>
  <table class="tbl"><tr><th>Student no.</th><th>Name</th><th style="width:140px">Grade</th><th>Remarks</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['user_no']) ?></td><td><?= e($r['last_name'] . ', ' . $r['first_name']) ?></td>
        <td><select name="grade[<?= (int)$r['id'] ?>"><?php foreach ($scale as $g): ?>
            <option value="<?= e($g) ?>" <?= (string)$r['grade'] === $g ? 'selected' : '' ?>><?= $g === '' ? '— none —' : e($g) ?></option><?php endforeach; ?></select></td>
        <td><?= $r['remarks'] ? badge($r['remarks']) : '' ?></td></tr>
  <?php endforeach; ?></table>
  <p><button class="btn">Save grades</button> <a href="<?= url('/registrar/grades') ?>">Back</a></p>
</form><?php endif; ?>
</div>