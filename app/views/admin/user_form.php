<?php
$edit = (bool)$user;
$v = fn($k, $d = '') => $edit ? ($user[$k] ?? $d) : old($k, $d);
$role = $edit ? $user['role'] : old('role', 'student');
$dept = $edit ? ($user['role'] === 'student' ? ($user['s_dept'] ?? '') : ($user['department_id'] ?? '')) : old('department_id');
?>
<h1><?= $edit ? 'Edit user' : 'New user' ?></h1>
<p class="sub"><?= $edit ? 'Role and ID number cannot be changed.' : 'A temporary password is generated and shown once; the user must change it at first login.' ?></p>
<div class="card">
  <form method="post" action="<?= url($edit ? "/admin/users/{$user['id']}" : '/admin/users') ?>" class="formgrid"><?= csrf_field() ?>
    <div><label>ID number</label><input name="user_no" value="<?= e($v('user_no')) ?>" <?= $edit ? 'readonly' : 'required' ?>></div>
    <div><label>Role</label>
      <?php if ($edit): ?><input value="<?= e(ucfirst($role)) ?>" readonly>
      <?php else: ?><select name="role"><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select><?php endif; ?></div>
    <div><label>First name</label><input name="first_name" value="<?= e($v('first_name')) ?>" required></div>
    <div><label>Last name</label><input name="last_name" value="<?= e($v('last_name')) ?>" required></div>
    <div><label>Email (optional)</label><input type="email" name="email" value="<?= e($v('email')) ?>"></div>
    <div><label>College / department</label><select name="department_id"><option value="0">— not applicable —</option>
      <?php foreach ($depts as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (int)$dept === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select>
      <span class="hint">Required for students and department staff.</span></div>
    <div><label>Program (students)</label><input name="program" value="<?= e($v('program')) ?>"></div>
    <div><label>Year level (students)</label><input type="number" name="year_level" min="1" max="6" value="<?= e($v('year_level', 1)) ?>"></div>
    <div style="grid-column:1/-1"><button class="btn"><?= $edit ? 'Save changes' : 'Create account' ?></button> <a href="<?= url('/admin/users') ?>">Cancel</a></div>
  </form>
</div>