<?php

declare(strict_types=1);

/**
 * PHPUnit / PHPStan bootstrap for the AssignColorsByDayOfWeek plugin.
 *
 * Execution order:
 *   1. Define a no-op t() stub so that calls to the Kanboard translation helper
 *      compile and run correctly without a live Kanboard instance.
 *   2. If the Kanboard vendor autoloader is present (e.g. inside the
 *      kanboard/kanboard Docker image at /var/www/app/vendor/autoload.php),
 *      load it so that PHPUnit/PHPStan can resolve the real Kanboard base
 *      classes and models.
 *   3. Otherwise, load the minimal class stubs in tests/Stubs/KanboardStubs.php
 *      for local development outside the Docker container.
 */

// 1. Translation helper stub — must be defined before any Kanboard source is
//    loaded, because some Kanboard files call t() at parse time.
if (!function_exists('t')) {
    /**
     * No-op translation stub for test context.
     *
     * @param  string $string The string to translate
     * @param  mixed  ...$args Optional sprintf arguments (ignored in tests)
     * @return string The original string unchanged
     */
    function t(string $string, mixed ...$args): string
    {
        return $string;
    }
}

// 2. Prefer Kanboard's real autoloader when running inside the Docker image.
//    This gives PHPStan and PHPUnit access to the full Kanboard type hierarchy.
if (file_exists('/var/www/app/vendor/autoload.php')) {
    require_once '/var/www/app/vendor/autoload.php';
} else {
    // 3. Local development fallback: minimal stub definitions for the Kanboard
    //    classes that AssignColorsByDayOfWeek depends on directly.
    require_once __DIR__ . '/Stubs/KanboardStubs.php';
}
