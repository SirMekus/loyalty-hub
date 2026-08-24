<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * A cached config/route file (e.g. baked by docker/entrypoint.sh on container boot,
 * for production-style performance) makes Laravel skip reading .env and environment
 * variables entirely, using the frozen cached values instead — which silently defeats
 * phpunit.xml's <php><env force="true"> test overrides below. 'composer test' clears
 * this via 'artisan config:clear' first, but that's easy to bypass by running
 * 'php artisan test' or 'vendor/bin/phpunit' directly. Clear it unconditionally here
 * instead, so the test suite is isolated no matter how it's invoked.
 */
foreach (['config.php', 'routes-v7.php'] as $cacheFile) {
    $path = __DIR__.'/../bootstrap/cache/'.$cacheFile;

    if (file_exists($path)) {
        unlink($path);
    }
}
