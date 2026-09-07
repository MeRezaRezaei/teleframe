<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Schema;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * Declared Telegram schema layer of this package (spec D5).
 *
 * Resolution order (first hit wins):
 *   1. packaged schema-manifest.json `layer` (written by teleframe:schema-update)
 *   2. composer root package `extra.telegram-layer` (release-time record)
 *   3. packaged methods-mtproto.json artifact `layer` (the API surface we ship)
 *   4. the wire layer EncryptedConnection::LAYER (floor; may intentionally differ)
 *
 * The wire layer and the schema layer are separate concerns on purpose
 * (see AGENTS note); never "fix" one to match the other.
 */
final class SchemaLayer
{
    /**
     * @param string|null $manifestPath explicit packaged schema-manifest.json path
     *        (testing/scratch seam); null resolves the packaged file.
     */
    public static function layer(?string $manifestPath = null): int
    {
        $path = $manifestPath ?? SchemaArtifacts::path('schema-manifest.json');
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded) && isset($decoded['layer']) && is_int($decoded['layer'])) {
                return $decoded['layer'];
            }
        }

        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3) . '/composer.json'), true);
        if (is_array($composer) && is_int($composer['extra']['telegram-layer'] ?? null)) {
            return $composer['extra']['telegram-layer'];
        }

        $artifact = json_decode((string) file_get_contents(SchemaArtifacts::path('methods-mtproto.json')), true);
        if (is_array($artifact) && isset($artifact['layer']) && is_int($artifact['layer'])) {
            return $artifact['layer'];
        }

        return EncryptedConnection::LAYER;
    }

    /**
     * Cache-salt primitive (Phase 5d): compiled/derived caches MUST be
     * salted with this, never with mtime alone — a layer bump invalidates
     * derived artifacts even when file mtimes are unchanged (gap constraint 4).
     */
    public static function cacheSalt(?string $manifestPath = null): string
    {
        return 'schema-layer-' . self::layer($manifestPath);
    }
}