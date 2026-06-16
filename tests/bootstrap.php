<?php

/**
 * PHPUnit bootstrap: inject APP_KEY at runtime so .env.testing stays secret-free.
 */
if (! getenv('APP_KEY')) {
    $key = 'base64:'.base64_encode(random_bytes(32));
    putenv("APP_KEY={$key}");
    $_ENV['APP_KEY'] = $key;
    $_SERVER['APP_KEY'] = $key;
}

require dirname(__DIR__).'/vendor/autoload.php';
