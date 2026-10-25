<?php $u = Auth::user(); $path = current_path(); ?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(($title ?? '') . ' · ' . config('app_name')) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head><body>
<?php if ($u): ?>
<header class="topbar noprint"><div class="bar">
  <a class="brand" href="<?= url(Auth::home($u['role'])) ?>">SSIS</a>
  <nav>
    <?php foreach (nav_for($u['role']) as $href => $label):
        $on = substr_count($href, '/') === 1 ? $path === $href : str_starts_with($path, $href); ?>
      <a href="<?= url($href) ?>" class="<?= $on ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="who">
    <a href="<?= url($u['role'] === 'student' ? '/student/profile' : '/password') ?>"><?= e($u['first_name']) ?></a>
    <span class="role"><?= e(ucfirst($u['role'])) ?></span>
    <form method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?><button class="btn outline sm">Log out</button></form>
  </div>
</div></header>
<?php endif; ?>
<main class="container">
  <?php foreach (flashes() as [$t, $m]): ?><div class="alert <?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>
  <?= $content ?>
</main>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body></html>