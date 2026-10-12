<?php

declare(strict_types=1);

/**
 * Worktree-first autoload bootstrap for phpunit.xml.dist.
 *
 * In the inspect worktree `vendor/` is a symlink to the MAIN tree's vendor
 * dir, so `vendor/autoload.php` maps every MeRezaRezaei\Teleframe\* and
 * Tests\* PSR-4 prefix to the MAIN tree's src/ and tests/ (the pre-fix
 * state). A verify run in this worktree must exercise THIS worktree's
 * src + tests, otherwise fixes on disk are invisible and pre-fix classes
 * (or a mid-state main tree, e.g. a purged Migrations dir) are loaded
 * instead. This bootstrap registers a prepend loader that resolves the
 * worktree paths first and falls through to the Composer loader for
 * anything the worktree does not have (vendor packages, etc.).
 *
 * Mnemonic ordering: more specific prefixes are tried first so
 * `MeRezaRezaei\Teleframe\Core\Tests\` wins over `MeRezaRezaei\Teleframe\Core\`.
 */

require dirname(__DIR__).'/vendor/autoload.php';

if (! defined('TELEFRAME_WORKTREE_BOOTSTRAPPED')) {
    $root = dirname(__DIR__);

    spl_autoload_register(static function (string $class) use ($root): void {
        static $map = null;

        if ($map === null) {
            $t = $root.'/tests';
            $s = $root.'/src';
            $map = [
                'MeRezaRezaei\\Teleframe\\Core\\Tests\\' => $t.'/Core',
                'MeRezaRezaei\\Teleframe\\Bot\\Tests\\' => $t.'/Bot',
                'MeRezaRezaei\\Teleframe\\Laravel\\Tests\\' => $t.'/Laravel',
                'MeRezaRezaei\\Teleframe\\Schema\\Tests\\' => $t.'/Schema',
                'MeRezaRezaei\\Teleframe\\Tests\\' => $t,
                'MeRezaRezaei\\Teleframe\\Core\\' => $s.'/Core',
                'MeRezaRezaei\\Teleframe\\Bot\\' => $s.'/Bot',
                'MeRezaRezaei\\Teleframe\\Laravel\\' => $s.'/Laravel',
                'MeRezaRezaei\\Teleframe\\Schema\\' => $s.'/Schema',
                'MeRezaRezaei\\Teleframe\\' => $s.'/Teleframe',
                'Tests\\' => $t,
            ];
        }

        foreach ($map as $prefix => $base) {
            if (! str_starts_with($class, $prefix)) {
                continue;
            }

            $file = $base.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            if (is_file($file)) {
                require $file;

                return;
            }
        }
    }, true, true);

    // Supersedes the per-test helper (global registration already covers
    // every src/tests prefix it mapped); mark it loaded so it no-ops.
    if (! defined('TELEFRAME_FIXES_AUTOLOADED')) {
        define('TELEFRAME_FIXES_AUTOLOADED', 1);
    }
    define('TELEFRAME_WORKTREE_BOOTSTRAPPED', 1);
}
