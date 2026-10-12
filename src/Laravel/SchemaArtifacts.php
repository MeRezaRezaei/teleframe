<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel;

use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts as PackagedSchemaArtifacts;
use RuntimeException;

/**
 * Locates the schema artifacts the mirror pipeline is built from.
 *
 * B3 RULING: these files ship INSIDE the package (schema/sources/*.tl and
 * schema/mirror-catalog.json), so they must resolve relative to the package
 * root — never the host application's base_path(). A host app installs
 * teleframe under vendor/merezarezaei/teleframe, so base_path() finds nothing
 * there and the mirror seams fataled with "scheme file not found" on a stock
 * install.
 *
 * The host base_path() probe that used to sit here as a "dev fallback" is
 * GONE, deliberately: it could only ever fire when the package root and the
 * host root differ — i.e. exactly the real-install case — so it was a
 * shadowing hazard, not a fallback. A host carrying its own
 * docs/superpowers/specs/*-telegram-mirror-catalog.json could satisfy such a
 * probe and silently serve the WRONG catalog to the NF5 truth path.
 *
 * Resolution is delegated to Schema\SchemaArtifacts, which already knows all
 * three consumption modes: the repo working copy, a path-repository symlink,
 * and a vendor/dist install (via Composer\InstalledVersions).
 */
final class SchemaArtifacts
{
    /** Packaged path of the TL source, relative to the package root. */
    public const TL_RELATIVE = 'schema/sources/TL_telegram_v227.tl';

    /**
     * Packaged path of the NF5 mirror catalog, relative to the package root.
     *
     * It lives in src/Schema/schema/ with the other generated schema
     * artifacts, NOT under docs/ — composer.json archive.exclude drops /docs
     * wholesale, which shipped this package with the NF5 truth path broken.
     */
    public const CATALOG_RELATIVE = 'src/Schema/schema/mirror-catalog.json';

    /** The package root (the directory holding composer.json). */
    public static function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function tl(): string
    {
        return self::locate(self::TL_RELATIVE);
    }

    public static function catalog(): string
    {
        $path = PackagedSchemaArtifacts::path(basename(self::CATALOG_RELATIVE));
        if (! is_file($path)) {
            throw new RuntimeException(
                'teleframe schema artifact not found: '.self::CATALOG_RELATIVE
                .' (looked in '.$path.' — the package install is incomplete).'
            );
        }

        return $path;
    }

    private static function locate(string $relative): string
    {
        $inPackage = self::packageRoot().'/'.$relative;
        if (is_file($inPackage)) {
            return $inPackage;
        }

        throw new RuntimeException(
            'teleframe schema artifact not found: '.$relative
            .' (looked in '.$inPackage.' — the package install is incomplete).'
        );
    }
}
