<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use PHPUnit\Framework\TestCase;

/**
 * The runtime schema artifacts must survive dist packing.
 *
 * The NF5 mirror catalog is loaded by the default
 * teleclient.ingest.mirror-ingester binding and by teleframe:ingest:gate at
 * the first update. It used to live under docs/superpowers/specs/, which
 * composer.json archive.exclude drops wholesale — so it resolved fine in the
 * repo and fataled with "teleframe schema artifact not found" on a stock
 * install. These assertions are expressed against the PACKAGING
 * configuration, not against the working tree, so a test run in a checkout
 * that happens to still contain docs/ cannot mask the breakage.
 */
final class MirrorCatalogDistPackagingTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    public function test_every_runtime_artifact_resolves_and_exists_in_the_package(): void
    {
        self::assertFileExists(SchemaArtifacts::tl());
        self::assertFileExists(SchemaArtifacts::catalog());
    }

    public function test_runtime_artifacts_are_not_excluded_from_the_distributed_archive(): void
    {
        $excluded = $this->archiveExcludes();

        foreach ([SchemaArtifacts::TL_RELATIVE, SchemaArtifacts::CATALOG_RELATIVE] as $relative) {
            self::assertFileExists(
                self::PACKAGE_ROOT.'/'.$relative,
                "runtime artifact [{$relative}] is missing from the package root"
            );

            foreach ($excluded as $pattern) {
                self::assertFalse(
                    $this->excludedBy($pattern, $relative),
                    "runtime artifact [{$relative}] is removed by archive.exclude entry [{$pattern}]"
                );
            }
        }
    }

    public function test_the_catalog_lives_inside_the_shipped_source_tree_not_in_docs(): void
    {
        $relative = SchemaArtifacts::CATALOG_RELATIVE;

        self::assertStringStartsWith('src/', $relative, 'the catalog must ship inside src/, not under docs/');
        self::assertStringNotContainsString('docs/', $relative);
        self::assertFileExists(self::PACKAGE_ROOT.'/'.$relative);
    }

    public function test_the_catalog_is_loadable_json(): void
    {
        $decoded = json_decode((string) file_get_contents(SchemaArtifacts::catalog()), true);

        self::assertIsArray($decoded);
        self::assertNotSame([], $decoded, 'the mirror catalog must not be empty');
    }

    public function test_runtime_artifacts_resolve_against_the_package_root_never_the_host_application(): void
    {
        if (! function_exists('base_path')) {
            self::markTestSkipped('no host application booted; nothing to shadow');
        }

        $packageRoot = realpath(SchemaArtifacts::packageRoot());
        self::assertIsString($packageRoot);

        foreach ([SchemaArtifacts::tl(), SchemaArtifacts::catalog()] as $resolved) {
            $real = realpath($resolved);
            self::assertIsString($real);
            self::assertStringStartsWith(
                $packageRoot.'/',
                $real,
                'a host application path shadowed the packaged schema artifact: '.$real
            );
        }
    }

    /** @return list<string> */
    private function archiveExcludes(): array
    {
        $composer = json_decode(
            (string) file_get_contents(self::PACKAGE_ROOT.'/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $excluded = $composer['archive']['exclude'] ?? [];
        self::assertIsArray($excluded);

        return array_values(array_map('strval', $excluded));
    }

    /**
     * composer archive excludes are path patterns: a leading slash anchors at
     * the package root, everything else is a path segment substring.
     */
    private function excludedBy(string $pattern, string $path): bool
    {
        $anchored = str_starts_with($pattern, '/');
        $pattern = trim($pattern, '/');

        if ($pattern === '') {
            return false;
        }

        if ($anchored) {
            return $path === $pattern || str_starts_with($path, $pattern.'/');
        }

        foreach (explode('/', $pattern) as $segment) {
            if ($segment === '' || $segment === '*') {
                continue;
            }
            if (str_contains('/'.$path.'/', '/'.$segment.'/')) {
                return true;
            }
        }

        return false;
    }
}
