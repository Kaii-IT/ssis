<h1>System settings</h1><p class="sub">Terms, departments and document fees.</p>
<div class="card"><h2>Academic terms</h2>
  <table class="tbl"><?php foreach ($terms as $t): ?>
    <tr><td><?= e($t['name']) ?></td><td><?= $t['is_current'] ? '<span class="badge ok">Current</span>' : '' ?></td>
        <td class="right"><?= $t['is_current'] ? '' : post_btn("/admin/terms/{$t['id']}/current", 'Set as current', 'btn outline sm', [], 'Switch the current term?') ?></td></tr>
  <?php endforeach; ?></table>
  <form method="post" action="<?= url('/admin/terms') ?>" class="row"><?= csrf_field() ?><input name="name" placeholder="2nd Semester 2026-2027" style="max-width:300px" required><button class="btn sm">Add term</button></form></div>
<div class="card"><h2>Departments &amp; offices</h2>
  <table class="tbl"><tr><th>Code</th><th>Name</th><th>Kind</th><th>Clearing</th></tr>
  <?php foreach ($depts as $d): ?><tr><td><?= e($d['code']) ?></td><td><?= e($d['name']) ?></td><td><?= e(ucfirst($d['kind'])) ?></td><td><?= $d['is_clearing'] ? 'Yes' : 'No' ?></td></tr><?php endforeach; ?></table>
  <form method="post" action="<?= url('/admin/departments') ?>" class="row"><?= csrf_field() ?>
    <input name="code" placeholder="Code" style="max-width:110px" required><input name="name" placeholder="Name" style="max-width:260px" required>
    <select name="kind" style="max-width:130px"><option value="college">College</option><option value="office">Office</option></select>
    <label style="margin:0"><input type="checkbox" name="is_clearing" value="1" checked style="width:auto"> Clearing</label><button class="btn sm">Add</button></form></div>
<div class="card"><h2>Document types &amp; fees</h2>
  <table class="tbl"><tr><th>Document</th><th>Fee (₱)</th><th>Requires</th><th>Active</th><th></th></tr>
  <?php foreach ($types as $t): ?><tr><form method="post" action="<?= url('/admin/doctypes/' . $t['id']) ?>"><?= csrf_field() ?>
    <td><?= e($t['name']) ?></td><td><input type="number" step="0.01" min="0" name="fee" value="<?= e($t['fee']) ?>" style="max-width:110px"></td>
    <td><select name="requirement" style="max-width:150px"><?php foreach (['none', 'enrollment', 'clearance'] as $r): ?><option <?= $t['requirement'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select></td>
    <td><input type="checkbox" name="is_active" value="1" <?= $t['is_active'] ? 'checked' : '' ?> style="width:auto"></td><td><button class="btn outline sm">Save</button></td></form></tr><?php endforeach; ?></table></div>