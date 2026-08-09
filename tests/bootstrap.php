<?php

/*
| PHPUnit bootstrap.
|
| The deploy containers inject the whole production .env into the process
| environment (docker compose env_file). PHPUnit's <env> entries in phpunit.xml
| cannot override those: they never touch $_SERVER, and Laravel's env() reads
| $_SERVER before anything else. So the test-only values are forced into every
| store here, before the application boots. Without this the suite would boot
| with APP_ENV=production — CSRF enabled, migrate:fresh refusing to run — and
| would connect to the production database.
*/

$testEnv = [
    'APP_ENV' => 'testing',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'CACHE_STORE' => 'array',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'DB_CONNECTION' => 'pgsql',
    'DB_DATABASE' => 'mutual_loan_fund_test',
    'DEFAULT_FUNCTIONAL_CURRENCY' => 'USDT',
    'BLOCKCHAIN_VERIFIER' => 'manual',
    'TELESCOPE_ENABLED' => 'false',
];

foreach ($testEnv as $key => $value) {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv("{$key}={$value}");
}

require __DIR__.'/../vendor/autoload.php';
