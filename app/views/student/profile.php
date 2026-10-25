<h1>My profile</h1><p class="sub">Official details are maintained by the Registrar; you can update your contact information.</p>
<div class="card narrow">
  <dl class="receipt" style="padding:0;border:0;margin:0 0 10px;max-width:none"><dl style="display:contents">
    <dt>Student no.</dt><dd><?= e($s['user_no']) ?></dd>
    <dt>Name</dt><dd><?= e($s['first_name'] . ' ' . $s['last_name']) ?></dd>
    <dt>College</dt><dd><?= e($s['dept_name']) ?></dd>
    <dt>Program</dt><dd><?= e($s['program']) ?>, Year <?= (int)$s['year_level'] ?></dd>
    <dt>Status</dt><dd><?= e(ucfirst($s['status'])) ?></dd>
  </dl></dl>
  <form method="post" action="<?= url('/student/profile') ?>"><?= csrf_field() ?>
    <label>Email</label><input type="email" name="email" value="<?= e($s['email']) ?>">
    <label>Contact number</label><input name="contact_no" value="<?= e($s['contact_no']) ?>">
    <label>Address</label><input name="address" maxlength="255" value="<?= e($s['address']) ?>">
    <p class="row"><button class="btn">Save changes</button><a class="btn outline" href="<?= url('/password') ?>">Change password</a></p>
  </form>
</div>