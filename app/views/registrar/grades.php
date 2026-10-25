<h1>Grades encoding</h1><p class="sub"><?= e($term['name']) ?> · choose a class to encode grades</p>
<div class="card"><table class="tbl"><tr><th>Subject</th><th>Section</th><th>Enrolled</th><th>Graded</th><th></th></tr>
<?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['code'] . ' · ' . $r['title']) ?></td><td><?= e($r['section']) ?></td><td><?= (int)$r['enrolled'] ?></td><td><?= (int)$r['graded'] ?></td>
      <td class="right"><a class="btn outline sm" href="<?= url('/registrar/grades/' . $r['id']) ?>">Encode</a></td></tr>
<?php endforeach; ?></table></div>