<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Handler;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * W-34: the zero-regex allow-list was enforced only statically by phpstan
 * (`disallowedFunctionCalls: preg_*`), so a future `allowIn` widening or a
 * regex slipping into the strictly zero-regex zones would go unnoticed by
 * the test gate. Pins the AGENTS.md hard rule directly: src/Core and
 * src/Teleframe/Handler must be free of preg_* (string funcs only), and
 * phpstan must NOT be widened to cover them.
 */
final class ZeroRegexW34Test extends TestCase
{
    private const ZONES = [
        __DIR__.'/../../../src/Core',
        __DIR__.'/../../../src/Teleframe/Handler',
    ];

    /** @return iterable<string> */
    private function phpFiles(string $root): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isDir() && str_ends_with($file->getFilename(), '.php')) {
                yield $file->getPathname();
            }
        }
    }

    public function test_strictly_zero_regex_zones_contain_no_preg_calls(): void
    {
        $offenders = [];
        foreach (self::ZONES as $zone) {
            self::assertDirectoryExists($zone, "zero-regex zone missing: {$zone}");
            foreach ($this->phpFiles($zone) as $path) {
                $code = (string) file_get_contents($path);
                if (str_contains($code, 'preg_')) {
                    $offenders[] = str_replace(__DIR__.'/../../../', '', $path);
                }
            }
        }

        self::assertSame([], $offenders, 'preg_* calls are banned in src/Core and src/Teleframe/Handler (AGENTS.md zero-regex rule)');
    }

    public function test_phpstan_allow_list_covers_only_the_allowed_dirs(): void
    {
        $config = (string) file_get_contents(__DIR__.'/../../../phpstan.neon.dist');

        foreach (['src/Laravel/*', 'src/Schema/*', 'src/Bot/*'] as $allowed) {
            self::assertStringContainsString($allowed, $config, 'expected zero-regex allow-list entry');
        }

        self::assertStringNotContainsString('src/Core/*', $config, 'src/Core must never be allow-listed');
        self::assertStringNotContainsString('src/Teleframe/Handler/*', $config, 'src/Teleframe/Handler must never be allow-listed');
    }
}
