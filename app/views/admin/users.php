<h1>Manage users</h1><p class="sub">Create, edit, deactivate and reset accounts.</p>
<form method="get" class="row" style="margin-bottom:12px">
  <input name="q" value="<?= e($q) ?>" placeholder="Search ID or name" style="max-width:260px">
  <select name="role" style="max-width:170px"><option value="">All roles</option><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select>
  <button class="btn outline sm">Filter</button><a class="btn sm" href="<?= url('/admin/users/new') ?>">New user</a>
</form>
<div class="card"><table class="tbl"><tr><th>ID no.</th><th>Name</th><th>Role</th><th>Dept</th><th>Status</th><th>Last login</th><th>Actions</th></tr>
<?php foreach ($rows as $u): $me = (int)$u['id'] === (int)Auth::user()['id']; ?>
  <tr><td><?= e($u['user_no']) ?></td><td><?= e($u['last_name'] . ', ' . $u['first_name']) ?></td><td><?= e(ucfirst($u['role'])) ?></td><td><?= e($u['dcode']) ?></td>
      <td><?= $u['is_active'] ? '<span class="badge ok">Active</span>' : '<span class="badge bad">Disabled</span>' ?></td><td class="muted"><?= e($u['last_login_at'] ?? 'never') ?></td>
      <td><a class="btn outline sm" href="<?= url("/admin/users/{$u['id']}/edit") ?>">Edit</a>
        <?= $me ? '' : post_btn("/admin/users/{$u['id']}/toggle", $u['is_active'] ? 'Disable' : 'Enable', 'btn outline sm', [], 'Change this account status?') ?>
        <?= post_btn("/admin/users/{$u['id']}/reset", 'Reset password', 'btn outline sm', [], 'Generate a new temporary password?') ?></td></tr>
<?php endforeach; ?></table></div>