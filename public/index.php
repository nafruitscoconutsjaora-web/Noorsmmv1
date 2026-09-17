<?php

declare(strict_types=1);

// SMM Panel Production Front Controller

define('APP_START', microtime(true));

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (is_string($uri) && (str_starts_with($uri, '/install') || $uri === '/install')) {
    $installFile = dirname(__DIR__) . $uri;
    if (is_dir($installFile)) {
        $installFile = rtrim($installFile, '/') . '/index.php';
    } elseif (!str_ends_with($installFile, '.php') && file_exists($installFile . '.php')) {
        $installFile = $installFile . '.php';
    }
    if (file_exists($installFile) && is_file($installFile)) {
        require $installFile;
        exit;
    }
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/app/Helpers/helpers.php';

$app = new App\Core\Application(dirname(__DIR__));
$app->run();
