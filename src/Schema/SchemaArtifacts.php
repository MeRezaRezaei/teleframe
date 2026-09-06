<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema;

use Composer\InstalledVersions;
use InvalidArgumentException;
use RuntimeException;

/**
 * Locates the packaged schema artifacts (methods-mtproto.json,
 * methods-botapi.json) and TL sources regardless of whether this package
 * is the repo working copy, a path-repo symlink, or a regular vendor install.
 */
final class SchemaArtifacts
{
    private const PACKAGE = 'merezarezaei/teleframe';

    /**
     * Absolute path to a file inside this package's schema/ directory.
     *
     * @param string $file relative path within schema/, e.g. "methods-mtproto.json" or "sources/api_full.tl"
     * @return string absolute path
     * @throws InvalidArgumentException on directory traversal attempts
     * @throws RuntimeException when the schema package path cannot be resolved
     */
    public static function path(string $file): string
    {
        if (str_contains($file, '..') || str_starts_with($file, '/') || str_starts_with($file, '\\')) {
            throw new InvalidArgumentException("Schema artifact path must not traverse outside schema/, got [{$file}].");
        }

        $root = null;
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled(self::PACKAGE, true)) {
            $root = InstalledVersions::getInstallPath(self::PACKAGE);
        }

        if ($root === null || $root === '') {
            $root = dirname(__DIR__);
        }

        if (!is_dir($root)) {
            throw new RuntimeException('teleframe package root could not be resolved.');
        }

        if ($root === dirname(__DIR__)) {
            return rtrim($root, '/') . '/Schema/schema/' . ltrim($file, '/');
        }
        return rtrim($root, '/') . '/src/Schema/schema/' . ltrim($file, '/');
    }

    /**
     * Absolute path to a source file under schema/sources/.
     *
     * @param string $file bare filename, e.g. "api_full.tl"
     * @return string absolute path
     */
    public static function source(string $file): string
    {
        if ($file !== basename($file)) {
            throw new InvalidArgumentException("Schema source name must be a bare filename, got [{$file}].");
        }

        return self::path('sources/' . $file);
    }
}
