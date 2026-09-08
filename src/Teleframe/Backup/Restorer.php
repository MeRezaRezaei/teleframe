<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use RuntimeException;

/**
 * Manifest-first restore (plan Phase 4): read the vault's LATEST manifest,
 * derive the set key from its salt, fetch + decrypt + reassemble every
 * referenced chunk, and rebuild the file tree under the target directory.
 * No server-side index is consulted — the manifest alone describes the set.
 */
final class Restorer
{
    /**
     * @return array{files: int, bytes: int}
     */
    public static function restore(VaultInterface $vault, string $passphrase, string $targetDir): array
    {
        $manifest = $vault->getLatestManifest();
        if ($manifest === null) {
            throw new RuntimeException('no manifest to restore');
        }

        $saltHex = $manifest['salt'] ?? null;
        if (!is_string($saltHex) || strlen($saltHex) !== 32 || !ctype_xdigit($saltHex)) {
            throw new RuntimeException('manifest is malformed: missing or invalid salt');
        }
        $salt = hex2bin($saltHex);
        if ($salt === false || strlen($salt) !== 16) {
            throw new RuntimeException('manifest is malformed: salt is not 16 bytes');
        }

        $entries = $manifest['files'] ?? null;
        if (!is_array($entries)) {
            throw new RuntimeException('manifest is malformed: files must be a list');
        }

        // Validate every path before touching the vault or the filesystem:
        // a hostile manifest must not restore (or create) anything.
        foreach ($entries as $entry) {
            if (!is_array($entry) || !is_string($entry['path'] ?? null) || !is_array($entry['chunks'] ?? null)) {
                throw new RuntimeException('manifest is malformed: each file needs path + chunks');
            }
            self::assertSafeRelativePath($entry['path']);
        }

        $key = VaultCrypto::deriveKey($passphrase, $salt);

        $files = 0;
        $bytes = 0;

        foreach ($entries as $entry) {
            $parts = [];
            foreach ($entry['chunks'] as $hash) {
                if (!is_string($hash)) {
                    throw new RuntimeException('manifest is malformed: chunk hash must be a string');
                }
                $parts[] = VaultCrypto::decryptChunk($key, $vault->getChunk($hash));
            }
            $content = Chunker::reassemble($parts);

            $destination = $targetDir . '/' . $entry['path'];
            $directory = dirname($destination);
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new RuntimeException("cannot create directory: {$directory}");
            }
            if (file_put_contents($destination, $content) === false) {
                throw new RuntimeException("cannot write restored file: {$destination}");
            }

            $files++;
            $bytes += strlen($content);
        }

        return ['files' => $files, 'bytes' => $bytes];
    }

    /**
     * Reject absolute paths, empty paths, NUL bytes, and any '..' segment —
     * string functions only (zero-regex policy), e.g. '../evil', 'a/../../x'.
     */
    private static function assertSafeRelativePath(string $path): void
    {
        if ($path === '' || $path[0] === '/' || str_contains($path, "\0")) {
            throw new RuntimeException("unsafe manifest path: {$path}");
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '..') {
                throw new RuntimeException("unsafe manifest path: {$path}");
            }
        }
    }
}
