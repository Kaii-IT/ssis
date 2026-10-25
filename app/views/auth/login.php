<div class="card login">
  <h1>Welcome to SSIS</h1>
  <p class="sub">CuyoTech University · Student Services Information System</p>
  <form method="post" action="<?= url('/login') ?>" autocomplete="off">
    <?= csrf_field() ?>
    <label for="user_no">ID number</label>
    <input id="user_no" name="user_no" value="<?= e(old('user_no')) ?>" placeholder="Student number or employee ID" required autofocus>
    <label for="password">Password</label>
    <input id="password" type="password" name="password" required autocomplete="current-password">
    <p class="hint">You will be taken to the portal that matches your account role. Accounts are created by the University Administrator.</p>
    <button class="btn block">Sign in</button>
  </form>
</div>