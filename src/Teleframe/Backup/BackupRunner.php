<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use InvalidArgumentException;
use RuntimeException;

/**
 * Scan a backup set → content-addressed chunks → encrypt → dedup against
 * the vault's CURRENT manifest → upload unique chunks → post the new
 * manifest LAST (plan Phase 4: manifest-first restore, no server index).
 *
 * Chunk keys are the PLAINTEXT sha256 from Chunker, deliberately not a
 * ciphertext hash: secretstream encryption is non-deterministic (fresh
 * random header per call), so ciphertext addressing would defeat cross-run
 * dedup. A stable plaintext key + a per-set salt reused from the latest
 * manifest keep chunk hashes identical across runs, making "second
 * unchanged run uploads nothing" structurally true.
 */
final class BackupRunner
{
    public const MANIFEST_VERSION = 1;

    private const DEFAULT_CHUNK_SIZE = 4 * 1024 * 1024;

    /**
     * @param array<string, mixed> $setConfig {paths: list<string>, excludes?: list<string>, chunk_size?: int, set?: string}
     * @return array{uploaded: int, skipped: int, files: int, bytes: int}
     */
    public static function run(array $setConfig, VaultInterface $vault, string $passphrase): array
    {
        $paths = $setConfig['paths'] ?? null;
        if (!is_array($paths) || $paths === []) {
            throw new InvalidArgumentException('setConfig must list at least one path');
        }
        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                throw new InvalidArgumentException('setConfig paths must be non-empty strings');
            }
        }

        $excludes = [];
        if (($setConfig['excludes'] ?? null) !== null) {
            if (!is_array($setConfig['excludes'])) {
                throw new InvalidArgumentException('setConfig excludes must be a list of strings');
            }
            $excludes = $setConfig['excludes'];
        }

        $chunkSize = self::DEFAULT_CHUNK_SIZE;
        if (($setConfig['chunk_size'] ?? null) !== null) {
            if (!is_int($setConfig['chunk_size']) || $setConfig['chunk_size'] < 1) {
                throw new InvalidArgumentException('setConfig chunk_size must be a positive integer');
            }
            $chunkSize = $setConfig['chunk_size'];
        }

        $setId = is_string($setConfig['set'] ?? null) ? $setConfig['set'] : '';

        $salt = self::saltForSet($vault);
        $key = VaultCrypto::deriveKey($passphrase, $salt);

        $known = self::previousChunkHashes($vault);

        $files = [];
        $chunkHashes = [];
        $uploaded = 0;
        $skipped = 0;
        $bytes = 0;

        foreach (self::collectFiles($paths, $excludes) as $absolute => $relative) {
            $data = file_get_contents($absolute);
            if ($data === false) {
                throw new RuntimeException("unreadable file: {$absolute}");
            }

            $bytes += strlen($data);
            $fileChunks = [];

            foreach (Chunker::split($data, $chunkSize) as $chunk) {
                $hash = $chunk['hash'];
                $fileChunks[] = $hash;
                $chunkHashes[$hash] = true;

                if (isset($known[$hash])) {
                    $skipped++;
                    continue;
                }

                $vault->putChunk($hash, VaultCrypto::encryptChunk($key, $chunk['data']));
                $known[$hash] = true;
                $uploaded++;
            }

            $files[] = ['path' => $relative, 'size' => strlen($data), 'chunks' => $fileChunks];
        }

        $manifest = [
            'version' => self::MANIFEST_VERSION,
            'set' => $setId,
            'salt' => bin2hex($salt),
            'created' => gmdate('c'),
            'files' => $files,
            'chunk_hashes' => array_keys($chunkHashes),
        ];

        $vault->putManifest(
            (string) json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );

        return ['uploaded' => $uploaded, 'skipped' => $skipped, 'files' => count($files), 'bytes' => $bytes];
    }

    /**
     * Stable per-set salt: reuse the latest manifest's salt when present so
     * the derived key — and therefore every chunk hash — stays identical
     * across runs; otherwise mint a fresh one for this new set.
     */
    private static function saltForSet(VaultInterface $vault): string
    {
        $manifest = $vault->getLatestManifest();
        $saltHex = is_array($manifest) ? ($manifest['salt'] ?? null) : null;

        if (is_string($saltHex) && strlen($saltHex) === 32 && ctype_xdigit($saltHex)) {
            return hex2bin($saltHex);
        }

        return VaultCrypto::newSalt();
    }

    /**
     * @return array<string, true>
     */
    private static function previousChunkHashes(VaultInterface $vault): array
    {
        $manifest = $vault->getLatestManifest();
        $hashes = is_array($manifest) ? ($manifest['chunk_hashes'] ?? null) : null;
        if (!is_array($hashes)) {
            return [];
        }

        $known = [];
        foreach ($hashes as $hash) {
            if (is_string($hash)) {
                $known[$hash] = true;
            }
        }

        return $known;
    }

    /**
     * Walk every path entry: directories recursively (RecursiveDirectoryIterator,
     * dots skipped, sorted for deterministic manifests), plain files as their
     * basename. Excludes are substring matches against the absolute path.
     *
     * @param list<string> $paths
     * @param list<mixed> $excludes
     * @return array<string, string> absolute path → manifest-relative path
     */
    private static function collectFiles(array $paths, array $excludes): array
    {
        $collected = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $absolute = $path;
                $relative = basename($path);
                if (!self::isExcluded($absolute, $excludes)) {
                    $collected[$absolute] = $relative;
                }
                continue;
            }

            if (!is_dir($path)) {
                throw new RuntimeException("backup set path not found: {$path}");
            }

            $root = rtrim($path, '/');
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $info) {
                /** @var \SplFileInfo $info */
                if (!$info->isFile()) {
                    continue;
                }
                $absolute = $info->getPathname();
                if (self::isExcluded($absolute, $excludes)) {
                    continue;
                }
                $relative = substr($absolute, strlen($root) + 1);
                $collected[$absolute] = $relative;
            }
        }

        ksort($collected);

        return $collected;
    }

    /**
     * @param list<mixed> $excludes
     */
    private static function isExcluded(string $absolute, array $excludes): bool
    {
        foreach ($excludes as $exclude) {
            if (is_string($exclude) && $exclude !== '' && str_contains($absolute, $exclude)) {
                return true;
            }
        }

        return false;
    }
}
