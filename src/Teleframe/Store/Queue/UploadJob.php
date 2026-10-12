<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Queue;

use InvalidArgumentException;

/**
 * UploadJob (verbatim S9): the JSON payload a queue carries for one store
 * put. Carries everything the worker needs to replay the upload without
 * the caller's process: bucket + object name, mode (sealed|raw), the
 * local source (absolute path, pre-read tempfile) the worker pulls bytes
 * from, the chunk plan sha256s to verify against, and the metadata
 * message to post afterwards. Rendered/parsed as plain JSON — no regex
 * anywhere near this surface.
 *
 * TWO hard rules on the wire form, both about what a queue message may NOT
 * carry:
 *
 *  1. NO PASSPHRASE. The seal secret is not in toArray() — only the boolean
 *     has_passphrase, the same shape PeerTarget::toRow() already ships. A
 *     persistent RabbitMQ message (or a Redis mirror, or a broker dump) must
 *     never be a vault of every sealed object's key. A sealed job therefore
 *     has no key material to replay with, and fromArray() says so LOUDLY
 *     rather than the worker silently uploading plaintext.
 *
 *  2. NO ARBITRARY PATH. source_path is confined to a registered workspace
 *     root (see allowSourceRoot), because whoever can publish to
 *     tstore.upload would otherwise get fopen() + unlink() on any path the
 *     worker can reach — a local file read exfiltrated to a Telegram channel,
 *     plus an arbitrary delete.
 */
final class UploadJob
{
    /**
     * The temp dir is the DEFAULT root because it is where StoreService
     * materializes bytes/stream sources (tempnam(sys_get_temp_dir(), ...)),
     * and it is the only root a plain-PHP consumer can know about. A host
     * that queues caller-supplied paths should register its own root(s) and
     * narrow this; an empty list means "sys_get_temp_dir() only".
     *
     * @var list<string>
     */
    private static array $roots = [];

    /**
     * @param  list<string>  $chunkShas  per-chunk sha256 (wire order) to verify against
     */
    public function __construct(
        public readonly int $accountId,
        public readonly string $bucket,
        public readonly string $name,
        public readonly string $mode, // sealed|raw
        public readonly string $sourcePath,
        public readonly int $size,
        public readonly array $chunkShas = [],
        public readonly ?string $passphrase = null,
    ) {}

    /**
     * Register a directory upload-job sources may live in. Registering never
     * widens an existing job — the check is realpath containment against the
     * whole set, so a symlink or `..` cannot escape it.
     */
    public static function allowSourceRoot(string $root): void
    {
        $real = realpath($root);
        if ($real === false) {
            return; // not a directory (yet) — registering it would grant nothing
        }

        if (! in_array($real, self::$roots, true)) {
            self::$roots[] = $real;
        }
    }

    /** The registered roots; [sys_get_temp_dir()] when the host registered none. */
    public static function sourceRoots(): array
    {
        return self::$roots === [] ? [realpath(sys_get_temp_dir()) ?: sys_get_temp_dir()] : self::$roots;
    }

    /** Drop the registered roots (tests; a host that re-registers per worker). */
    public static function forgetSourceRoots(): void
    {
        self::$roots = [];
    }

    /**
     * The wire form. NEVER the passphrase — see the class docblock.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'bucket' => $this->bucket,
            'name' => $this->name,
            'mode' => $this->mode,
            'source_path' => $this->sourcePath,
            'size' => $this->size,
            'chunk_shas' => $this->chunkShas,
            'has_passphrase' => $this->passphrase !== null && $this->passphrase !== '',
        ];
    }

    public static function fromArray(array $payload): self
    {
        $accountId = $payload['account_id'] ?? null;
        $bucket = $payload['bucket'] ?? null;
        $name = $payload['name'] ?? null;
        $mode = $payload['mode'] ?? null;
        $sourcePath = $payload['source_path'] ?? null;
        $size = $payload['size'] ?? null;
        if (! is_int($accountId)
            || ! is_string($bucket)
            || ! is_string($name)
            || ! in_array($mode, ['sealed', 'raw'], true)
            || ! is_string($sourcePath)
            || ! is_int($size)
        ) {
            throw new InvalidArgumentException('upload job payload is missing required fields');
        }

        $chunkShas = $payload['chunk_shas'] ?? [];
        $chunkShas = is_array($chunkShas) ? array_values(array_map('strval', $chunkShas)) : [];

        // Refuse the arbitrary-path read/delete BEFORE anything can open or
        // unlink it: the worker is the one holding the privileges, the queue
        // payload is the untrusted half.
        self::confine($sourcePath);

        // A sealed job's secret is deliberately not on the wire. Say so here,
        // loudly, instead of letting the worker upload it unsealed.
        if ($mode === 'sealed' && self::declaresPassphrase($payload)) {
            throw new InvalidArgumentException(
                'sealed upload jobs cannot ride the queue: the wire payload carries has_passphrase only '
                .'(never the secret), so the worker has no key. Seal the object with the sync driver, '
                .'or resolve the passphrase from a keyring seam the worker holds.'
            );
        }

        return new self(
            $accountId,
            $bucket,
            $name,
            $mode,
            $sourcePath,
            $size,
            $chunkShas,
        );
    }

    /** Whether the wire payload claims the job was sealed. */
    private static function declaresPassphrase(array $payload): bool
    {
        if (isset($payload['passphrase']) && is_string($payload['passphrase']) && $payload['passphrase'] !== '') {
            return true;
        }

        return ($payload['has_passphrase'] ?? false) === true;
    }

    /**
     * Confine $path to a registered source root.
     *
     * realpath() does the work: it resolves symlinks and `..` before the
     * comparison, so neither can point the job outside the root, and a path
     * that does not resolve to a file is refused outright. The check is
     * containment (root + separator prefix), never a string prefix.
     *
     * @throws InvalidArgumentException when the path escapes every root
     */
    private static function confine(string $path): void
    {
        $real = realpath($path);
        if ($real === false || ! is_file($real)) {
            throw new InvalidArgumentException(
                'upload job source does not resolve to a readable file (refused before any open/unlink)'
            );
        }

        foreach (self::sourceRoots() as $root) {
            $root = rtrim($root, '/\\');
            if ($real === $root || str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
                return;
            }
        }

        throw new InvalidArgumentException(
            'upload job source is outside every registered workspace root (refused): the queue may not '
            .'name an arbitrary path for the worker to read or delete'
        );
    }
}
