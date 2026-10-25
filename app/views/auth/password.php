<div class="card narrow">
  <h1>Change password</h1>
  <p class="sub"><?= $forced ? 'You are using a temporary password. Set a new one to continue.' : 'Use at least 8 characters with letters and numbers.' ?></p>
  <form method="post" action="<?= url('/password') ?>"><?= csrf_field() ?>
    <label>Current password</label><input type="password" name="current" required autocomplete="current-password">
    <label>New password</label><input type="password" name="new" required minlength="8" autocomplete="new-password">
    <label>Confirm new password</label><input type="password" name="confirm" required minlength="8" autocomplete="new-password">
    <p><button class="btn">Update password</button></p>
  </form>
</div>