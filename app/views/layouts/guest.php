<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(($title ?? '') . ' · ' . config('app_name')) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head><body>
<main class="container">
  <?php foreach (flashes() as [$t, $m]): ?><div class="alert <?= e($t) ?>" style="max-width:400px;margin:20px auto 0"><?= e($m) ?></div><?php endforeach; ?>
  <?= $content ?>
</main>
</body></html>