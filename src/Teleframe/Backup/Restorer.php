<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use RuntimeException;

/**
 * Manifest-first restore (plan Phase 4): read the vault's LATEST manifest,
 * derive the set key from its salt, fetch + decrypt + reassemble every
 * referenced chunk, and rebuild the file tree under the target directory.
 * No server-side index is consulted — the manifest alone describes the set.
 *
 * EVERY DECRYPTED CHUNK IS PROVEN AGAINST ITS CONTENT ADDRESS BEFORE IT IS
 * USED, and the whole set is materialised in a STAGING directory and only then
 * moved into the target. Both are load-bearing, and neither is a nicety:
 *
 *  - The AEAD proves the bytes are AUTHENTIC, not that they are the bytes
 *    ASKED FOR. An attacker who can post in the backup channel downloads any
 *    existing chunk's ciphertext and re-uploads it under another chunk's
 *    document name: it decrypts perfectly and reassembles into the wrong
 *    content, silently. Verifier::verifyWithKey() catches that substitution;
 *    restore is a separate action that is never chained to verify
 *    (BackupCommand restoreAction vs verifyAction), so the check has to be on
 *    the restore path itself or it does not exist for anyone who restores.
 *  - Because every path is validated up-front but every chunk is fetched
 *    lazily, a failure on file 5 of 10 used to leave files 1-4 written and
 *    the target holding a plausible-looking PARTIAL tree with nothing marking
 *    it incomplete. Staging makes a fetch/decrypt/verify failure leave the
 *    target untouched.
 */
final class Restorer
{
    /**
     * Reserved subdirectory of the target that holds the in-progress restore.
     * A manifest path whose FIRST segment is this name is rejected: otherwise
     * a manifest could name a file inside the staging area and have it moved
     * out from under the restore that is writing it.
     */
    private const STAGING_DIR = '.teleframe-staging';

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
        if (! is_string($saltHex) || strlen($saltHex) !== 32 || ! ctype_xdigit($saltHex)) {
            throw new RuntimeException('manifest is malformed: missing or invalid salt');
        }
        $salt = hex2bin($saltHex);
        if ($salt === false || strlen($salt) !== 16) {
            throw new RuntimeException('manifest is malformed: salt is not 16 bytes');
        }

        $entries = $manifest['files'] ?? null;
        if (! is_array($entries)) {
            throw new RuntimeException('manifest is malformed: files must be a list');
        }

        // Validate every path before touching the vault or the filesystem:
        // a hostile manifest must not restore (or create) anything.
        foreach ($entries as $entry) {
            if (! is_array($entry) || ! is_string($entry['path'] ?? null) || ! is_array($entry['chunks'] ?? null)) {
                throw new RuntimeException('manifest is malformed: each file needs path + chunks');
            }
            self::assertSafeRelativePath($entry['path']);

            // The staging directory is this restore's own scratch space: a
            // manifest that names a file INSIDE it would have that file moved
            // out from under the restore writing it, and the two would race.
            $segments = explode('/', $entry['path']);
            if ($segments[0] === self::STAGING_DIR) {
                throw new RuntimeException("unsafe manifest path: {$entry['path']}");
            }
        }

        $key = VaultCrypto::deriveKey($passphrase, $salt);

        $files = 0;
        $bytes = 0;

        $stagingDir = $targetDir.'/'.self::STAGING_DIR;
        // A leftover staging directory from a crashed run is removed first: it
        // is this class's own scratch space, never caller data, and restoring
        // into it would merge two sets.
        self::removeTree($stagingDir);
        self::ensureRestoreDir($stagingDir);

        /** @var list<string> $staged absolute paths, in manifest order */
        $staged = [];

        try {
            foreach ($entries as $entry) {
                $parts = [];
                foreach ($entry['chunks'] as $hash) {
                    if (! is_string($hash)) {
                        throw new RuntimeException('manifest is malformed: chunk hash must be a string');
                    }
                    $plain = VaultCrypto::decryptChunk($key, $vault->getChunk($hash));

                    // THE CONTENT-ADDRESS CHECK. The AEAD authenticated the blob,
                    // but nothing so far proved it is the blob the manifest asked
                    // for — a substituted chunk is authentic and still wrong.
                    // hash_equals is constant-time, as the address comparison in
                    // Verifier::verifyWithKey() is.
                    if (! hash_equals($hash, hash('sha256', $plain))) {
                        throw new RuntimeException(
                            "chunk {$hash} does not match its content address: the vault served authentic but different bytes"
                        );
                    }

                    $parts[] = $plain;
                }
                $content = Chunker::reassemble($parts);

                // The manifest records each file's plaintext size
                // (BackupRunner::run, files[] entries). A reassembly that does
                // not add up to it means the chunk list is incomplete or wrong.
                // Checked ONLY when the manifest carries an int size: a legacy
                // manifest without one is not rejected over a field it never had.
                $declaredSize = $entry['size'] ?? null;
                if (is_int($declaredSize) && strlen($content) !== $declaredSize) {
                    throw new RuntimeException(sprintf(
                        'restored file %s is %d bytes but the manifest declares %d',
                        $entry['path'],
                        strlen($content),
                        $declaredSize,
                    ));
                }

                $destination = $stagingDir.'/'.$entry['path'];
                $directory = dirname($destination);
                // Symlink/hostile-destination checks BEFORE any mkdir: a planted
                // symlink under the staging area must never steer a write outside it.
                self::assertWriteTargetSafe($stagingDir, $destination);
                self::ensureRestoreDir($directory);
                if (file_put_contents($destination, $content) === false) {
                    throw new RuntimeException("cannot write restored file: {$destination}");
                }
                @chmod($destination, 0600); // restored data can be a credential dump; lockdown

                $staged[] = $destination;
                $files++;
                $bytes += strlen($content);
            }

            // The whole set verified. Only now does anything reach the target:
            // same-filesystem rename() per file, so each file appears whole and
            // a failure above leaves the target with nothing of this set in it.
            foreach ($staged as $stagedPath) {
                $relative = substr($stagedPath, strlen($stagingDir) + 1);
                $destination = $targetDir.'/'.$relative;
                self::assertWriteTargetSafe($targetDir, $destination);
                self::ensureRestoreDir(dirname($destination));
                if (! @rename($stagedPath, $destination)) {
                    throw new RuntimeException("cannot move restored file into place: {$destination}");
                }
                @chmod($destination, 0600);
            }
        } finally {
            self::removeTree($stagingDir);
        }

        return ['files' => $files, 'bytes' => $bytes];
    }

    /**
     * Remove a directory tree created by this restore, best effort. Scoped to
     * the staging directory only — it is never called with caller data, and a
     * failure here must not mask the exception that triggered the cleanup.
     */
    private static function removeTree(string $directory): void
    {
        if (is_link($directory) || ! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $info) {
            /** @var \SplFileInfo $info */
            if ($info->isDir() && ! $info->isLink()) {
                @rmdir($info->getPathname());
            } else {
                @unlink($info->getPathname());
            }
        }

        @rmdir($directory);
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

    /**
     * Guards the write target against symlink write-through: every path
     * component between $targetDir and $destination is checked with is_link
     * (walking down, so a link planted anywhere in the chain is caught before
     * any mkdir), and an existing non-link DIRECTORY at the destination is
     * rejected too (a file cannot replace a directory). A pre-existing regular
     * file stays overwriteable — that is what restore means.
     */
    private static function assertWriteTargetSafe(string $targetDir, string $destination): void
    {
        $relative = substr($destination, strlen($targetDir) + 1);
        $probe = $targetDir;
        $segments = explode('/', $relative);
        $last = count($segments) - 1;

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }
            $probe .= '/'.$segment;
            if (is_link($probe)) {
                throw new RuntimeException("restore refused: symlink in restore path: {$probe}");
            }
            if ($index === $last && is_dir($probe)) {
                throw new RuntimeException("restore refused: destination is a directory: {$probe}");
            }
        }
    }

    /**
     * Creates the destination directory tree with mode 0700, then re-applies
     * chmod 0700 on exactly the directories THIS restore created (recursive
     * mkdir applies its mode only through the umask; parent directories that
     * already existed before the restore are left untouched). A path already
     * existing as a directory needs no action.
     */
    private static function ensureRestoreDir(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        $created = [];
        $probe = $directory;
        while (! is_dir($probe) && ! is_link($probe)) {
            $created[] = $probe;
            $parent = dirname($probe);
            if ($parent === $probe) {
                break;
            }
            $probe = $parent;
        }

        if (! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException("cannot create directory: {$directory}");
        }
        foreach ($created as $dir) {
            @chmod($dir, 0700);
        }
    }
}
