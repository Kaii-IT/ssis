<?php
return [
    'app_name'         => 'CuyoTech SSIS',
    'debug'            => true,            // set false in production
    'db' => ['host' => '127.0.0.1', 'name' => 'ssis', 'user' => 'ssis_user', 'pass' => 'password', 'charset' => 'utf8mb4'],
    'tuition_per_unit' => 1000.00,
    'misc_fee'         => 2500.00,
    'max_units'        => 24,
    'session_timeout'  => 1800,            // seconds of inactivity
];