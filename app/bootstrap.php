<?php
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

$GLOBALS['config'] = require APP . '/config/config.php';
error_reporting(E_ALL);
ini_set('display_errors', $GLOBALS['config']['debug'] ? '1' : '0');
date_default_timezone_set('Asia/Manila');

foreach (['helpers', 'Database', 'Auth', 'Csrf', 'Router'] as $f) {
    require APP . "/core/$f.php";
}
spl_autoload_register(function ($c) {
    $f = APP . "/controllers/$c.php";
    if (is_file($f)) require $f;
});