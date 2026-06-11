<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

function setupEnvironment(ClassLoader $autoloader): void
{
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('date.timezone', 'UTC');

    error_reporting(E_ALL);

    if (!extension_loaded('zend-opcache')) {
        return;
    }

    opcache_reset();
}
