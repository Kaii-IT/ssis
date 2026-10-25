<h1>Subjects &amp; offerings</h1><p class="sub"><?= e($term['name']) ?> · add or remove classes and slots</p>
<div class="card"><h2>Add offering</h2>
  <form method="post" action="<?= url('/registrar/offerings') ?>" class="formgrid"><?= csrf_field() ?>
    <div><label>Subject</label><select name="subject_id"><?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['code'] . ' — ' . $s['title']) ?></option><?php endforeach; ?></select></div>
    <div><label>Section</label><input name="section" value="A" maxlength="10" required></div>
    <div><label>Schedule</label><input name="schedule" placeholder="MWF 8:00-9:00" required></div>
    <div><label>Room</label><input name="room" required></div>
    <div><label>Slots</label><input type="number" name="slots" min="0" max="500" value="40" required></div>
    <div style="align-self:end"><button class="btn">Add offering</button></div>
  </form></div>
<div class="card"><h2>Offered this term</h2>
  <table class="tbl"><tr><th>Code</th><th>Subject</th><th>Sec</th><th>Schedule</th><th>Slots</th><th>Enrolled</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['code']) ?></td><td><?= e($r['title']) ?></td><td><?= e($r['section']) ?></td><td><?= e($r['schedule']) ?> · <?= e($r['room']) ?></td>
        <td><?= (int)$r['slots'] ?></td><td><?= (int)$r['taken'] ?></td>
        <td class="right"><?= $r['taken'] ? '' : post_btn("/registrar/offerings/{$r['id']}/delete", 'Remove', 'btn outline sm', [], 'Remove this offering?') ?></td></tr>
  <?php endforeach; ?></table></div>
<div class="card"><h2>Create subject</h2>
  <form method="post" action="<?= url('/registrar/subjects') ?>" class="formgrid"><?= csrf_field() ?>
    <div><label>Code</label><input name="code" placeholder="CS301" required></div>
    <div><label>Title</label><input name="title" required></div>
    <div><label>Units</label><input type="number" name="units" min="1" max="6" value="3" required></div>
    <div><label>College</label><select name="department_id"><option value="0">General education (all colleges)</option>
      <?php foreach ($depts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
    <div style="align-self:end"><button class="btn">Create subject</button></div>
  </form></div>