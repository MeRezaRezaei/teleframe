<?php

declare(strict_types=1);

/**
 * W-01/W-04/W-43 test bootstrap helper: register the WORKTREE src tree on the
 * front of the autoload stack.
 *
 * vendor/ is a symlink to the main tree, so vendor/autoload.php maps
 * MeRezaRezaei\Teleframe\* to the MAIN tree src (the pre-fix classes). A test
 * run in the inspect worktree must test the worktree's own generator/command
 * classes — this prepend makes the worktree win for anything not yet loaded.
 *
 * Idempotent: only the first require registers the loader (a global
 * marker constant guards against double registration across test classes).
 */
$root = dirname(__DIR__, 2);

if (! defined('TELEFRAME_FIXES_AUTOLOADED')) {
    $root = dirname(__DIR__, 2);

    spl_autoload_register(static function (string $class) use ($root): void {
        $map = [
            'MeRezaRezaei\\Teleframe\\Schema\\' => '/src/Schema',
            'MeRezaRezaei\\Teleframe\\Laravel\\' => '/src/Laravel',
            'MeRezaRezaei\\Teleframe\\Core\\' => '/src/Core',
            'MeRezaRezaei\\Teleframe\\Bot\\' => '/src/Bot',
            'MeRezaRezaei\\Teleframe\\' => '/src/Teleframe',
        ];
        foreach ($map as $prefix => $base) {
            if (str_starts_with($class, $prefix)) {
                $file = $root.$base.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
                if (is_file($file)) {
                    require $file;

                    return;
                }
            }
        }
    }, true, true);

    define('TELEFRAME_FIXES_AUTOLOADED', 1);
}
