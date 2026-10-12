<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

/** schema-manifest.json payload builder (spec §5.4). */
final class Manifest
{
    /**
     * @param array<string, string> $tableMap table name => migration file
     * @param array<string, array{stated:string, computed:string}> $crcMismatches
     */
    public static function build(
        int $layer,
        array $counts,
        array $tableMap,
        int $fkCount,
        array $crcMismatches,
    ): array {
        $manifest = [
            'layer' => $layer,
            'counts' => $counts,
            'tables' => $tableMap,
            'fk_count' => $fkCount,
            'crc_mismatches' => $crcMismatches,
        ];
        $manifest['hash'] = self::hash($manifest);
        return $manifest;
    }

    public static function hash(array $manifest): string
    {
        unset($manifest['hash']);
        ksort($manifest);
        array_walk_recursive($manifest, static function (&$v): void {
            if (is_array($v)) {
                ksort($v);
            }
        });
        return hash('sha256', (string) json_encode($manifest, JSON_UNESCAPED_SLASHES));
    }
}
