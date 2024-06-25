<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

try {
    $dotEnv = new Dotenv();
    $dotEnv->loadEnv(dirname(__DIR__) . '/.env');

    defined('JPATH_BASE') || define(
        'JPATH_BASE',
        $_SERVER['YOUR_JOOMLA_DIRECTORY'] ?? $_ENV['YOUR_JOOMLA_DIRECTORY']
    );
    defined('JPATH_PUBLIC') || define(
        'JPATH_PUBLIC',
        '/var/www/'. basename($_SERVER['YOUR_JOOMLA_DIRECTORY'] ?? $_ENV['YOUR_JOOMLA_DIRECTORY'])
    );
    defined('EXTENSION_ROOT') || define('EXTENSION_ROOT', dirname(__DIR__));
    defined('PROJECT_ROOT') || define('PROJECT_ROOT', __DIR__);
    defined('PROJECT_TEST') || define('PROJECT_TEST', __DIR__ . '/Tests/');
    defined('API_CONFIG_INI') || define('API_CONFIG_INI', __DIR__ . '/api-config.ini');

    $_SERVER['HTTP_HOST'] ??= 'https://example.org';
    $_SERVER['REQUEST_URI'] ??= '/index.php';

    require_once PROJECT_TEST . 'bootstrap.php';
} catch (Throwable $e) {
    echo $e->getMessage() . basename($e->getFile()) . ':' . $e->getLine() . PHP_EOL;
}
