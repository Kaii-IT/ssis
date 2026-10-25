<div class="card narrow">
  <h1><?= (int)$code ?> · <?= e($title) ?></h1>
  <p class="sub"><?= e($msg ?: 'Something went wrong.') ?></p>
  <?php $u = Auth::user(); ?>
  <a class="btn" href="<?= url($u ? Auth::home($u['role']) : '/login') ?>">Go back</a>
</div>