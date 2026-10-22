<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use InvalidArgumentException;
use RuntimeException;

/**
 * TStore object descriptor (manifest v2). One per stored object; posted
 * as the pretty-JSON metadata message (MetadataMessage) and persisted
 * as the tf_store_objects truth row.
 *
 * Shape:
 *   {version: 2, name, size, sha256, mode: raw|sealed, mime, salt?
 *    (hex, sealed only), created, chunks: [{n, size, sha256, wire_sha256?}]}
 *
 * chunk.sha256 is the PLAINTEXT content address (dedup + verify); sealed
 * chunks additionally carry wire_sha256 (ciphertext hash) because the
 * sealed document must be named by its ciphertext, never by plaintext
 * (naming a sealed doc by its plaintext hash would leak presence).
 * raw chunks omit wire_sha256 — the doc is named by plaintext hash.
 *
 * TRUST (the manifest is a plain chat message anyone who can post in the
 * bucket can EDIT, so a parser that merely accepts well-formed JSON is
 * not a trust boundary — see finding H4):
 *
 *  1. fromArray() REFUSES a chunk list whose `n` values are not exactly
 *     0..count-1 in ascending order. A reordered / gapped / duplicated
 *     chunk list is the one corruption every per-chunk check still
 *     accepts (each chunk is fetched, decrypted and hashed against the
 *     SAME unauthenticated expectation, so a reordering passes them all),
 *     so it is rejected at parse time rather than tolerated.
 *  2. `mac` (optional, sealed objects only) is a KEYED BLAKE2b over the
 *     canonical manifest body, keyed by the object's own derived key —
 *     see ManifestMac. Raw objects carry no key and therefore no mac;
 *     they are integrity-checked (whole-object sha256) but not
 *     authenticated, which is the honest limit of keyless storage.
 *     A mac is NEVER derived from a value the channel audience already
 *     holds (an access hash, a channel id, a message id): such a value
 *     is public to the attacker, so it authenticates nothing.
 *
 * COMPATIBILITY: `mac` is optional in the shape, so a v2 manifest parses
 * either way, but a SEALED manifest without one is refused at read time
 * (StoreService::assertManifestAuthenticated) — it predates this field and
 * nothing can vouch for it. Re-put the object to mint one.
 */
final class ObjectManifest
{
    public const VERSION = 2;

    public const MODE_RAW = 'raw';

    public const MODE_SEALED = 'sealed';

    /** @var list<array{n: int, size: int, sha256: string, wire_sha256?: string}> */
    private array $chunks;

    private function __construct(
        private readonly string $name,
        private readonly int $size,
        private readonly string $sha256,
        private readonly string $mode,
        private readonly string $mime,
        private readonly ?string $saltHex,
        private readonly string $created,
        array $chunks,
        private readonly ?string $macHex = null,
    ) {
        $this->chunks = $chunks;
    }

    /**
     * @param  list<array{n: int, size: int, sha256: string, wire_sha256?: string}>  $chunks
     */
    public static function new(
        string $name,
        int $size,
        string $sha256,
        string $mode,
        string $mime,
        ?string $saltHex,
        array $chunks,
        ?string $macHex = null,
    ): self {
        if ($name === '') {
            throw new InvalidArgumentException('object name must be non-empty');
        }
        if (! in_array($mode, [self::MODE_RAW, self::MODE_SEALED], true)) {
            throw new InvalidArgumentException("mode must be raw|sealed, got {$mode}");
        }
        if ($mode === self::MODE_SEALED && ($saltHex === null || $saltHex === '')) {
            throw new InvalidArgumentException('sealed objects require a salt');
        }
        if ($mode === self::MODE_RAW && $saltHex !== null) {
            throw new InvalidArgumentException('raw objects carry no salt');
        }
        self::assertHex64($sha256, 'sha256');
        if ($macHex !== null) {
            self::assertHex64($macHex, 'mac');
        }
        self::assertChunkOrder($chunks);

        return new self($name, $size, $sha256, $mode, $mime, $saltHex, gmdate('c'), $chunks, $macHex);
    }

    /**
     * The same manifest with its keyed MAC attached. The MAC is computed
     * over the body WITHOUT the mac field (ManifestMac::compute), so
     * attaching it afterwards is not circular.
     */
    public function withMac(?string $macHex): self
    {
        return new self(
            $this->name,
            $this->size,
            $this->sha256,
            $this->mode,
            $this->mime,
            $this->saltHex,
            $this->created,
            $this->chunks,
            $macHex,
        );
    }

    /**
     * Parse a decoded JSON payload (as carried by the metadata message
     * or the truth row). Accepts v2 only.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $version = $data['version'] ?? null;
        if ($version !== self::VERSION) {
            throw new RuntimeException('unsupported manifest version');
        }

        $name = $data['name'] ?? null;
        $size = $data['size'] ?? null;
        $sha256 = $data['sha256'] ?? null;
        $mode = $data['mode'] ?? null;
        $mime = $data['mime'] ?? null;
        $salt = $data['salt'] ?? null;
        $created = $data['created'] ?? null;
        $chunks = $data['chunks'] ?? null;
        $mac = $data['mac'] ?? null;

        if (! is_string($name) || $name === '') {
            throw new RuntimeException('manifest name requires a non-empty string');
        }
        if (! is_int($size) || $size < 0) {
            throw new RuntimeException('manifest size requires an int >= 0');
        }
        if (! is_string($sha256) || ! self::isHex64($sha256)) {
            throw new RuntimeException('manifest sha256 requires hex64');
        }
        if (! is_string($mode) || ! in_array($mode, [self::MODE_RAW, self::MODE_SEALED], true)) {
            throw new RuntimeException('manifest mode requires raw|sealed');
        }
        if (! is_string($mime)) {
            throw new RuntimeException('manifest mime requires a string');
        }
        if (! is_string($created)) {
            throw new RuntimeException('manifest created requires a string');
        }
        if (! is_array($chunks)) {
            throw new RuntimeException('manifest chunks requires a list');
        }

        $parsed = [];
        foreach ($chunks as $chunk) {
            if (! is_array($chunk) || ! is_int($chunk['n'] ?? null) || ! is_int($chunk['size'] ?? null)) {
                throw new RuntimeException('manifest chunk requires {n, size}');
            }
            $hash = $chunk['sha256'] ?? null;
            if (! is_string($hash) || ! self::isHex64($hash)) {
                throw new RuntimeException('manifest chunk sha256 requires hex64');
            }
            $wire = $chunk['wire_sha256'] ?? null;
            if ($wire !== null && (! is_string($wire) || ! self::isHex64($wire))) {
                throw new RuntimeException('manifest chunk wire_sha256 requires hex64');
            }
            $parsed[] = $wire === null
                ? ['n' => $chunk['n'], 'size' => $chunk['size'], 'sha256' => $hash]
                : ['n' => $chunk['n'], 'size' => $chunk['size'], 'sha256' => $hash, 'wire_sha256' => $wire];
        }

        if ($mac !== null && (! is_string($mac) || ! self::isHex64($mac))) {
            throw new RuntimeException('manifest mac requires hex64');
        }

        // Refuse a reordered / gapped / duplicated chunk list HERE, at parse
        // time. Reordering is the one corruption every per-chunk check still
        // accepts: each chunk is fetched and hashed against the expectation
        // carried by this very (unauthenticated) manifest, so a swapped `n`
        // passes all of them while the restored object is silently wrong.
        self::assertChunkOrder($parsed);

        if ($mode === self::MODE_SEALED && (! is_string($salt) || $salt === '')) {
            throw new RuntimeException('sealed manifest requires a salt');
        }
        if ($mode === self::MODE_RAW && $salt !== null) {
            throw new RuntimeException('raw manifest carries no salt');
        }

        return new self($name, $size, $sha256, $mode, $mime, is_string($salt) ? $salt : null, $created, $parsed, is_string($mac) ? $mac : null);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function mime(): string
    {
        return $this->mime;
    }

    public function saltHex(): ?string
    {
        return $this->saltHex;
    }

    /** The keyed manifest MAC, or null for a raw (keyless) manifest. */
    public function macHex(): ?string
    {
        return $this->macHex;
    }

    public function created(): string
    {
        return $this->created;
    }

    /**
     * @return list<array{n: int, size: int, sha256: string, wire_sha256?: string}>
     */
    public function chunks(): array
    {
        return $this->chunks;
    }

    public function chunkCount(): int
    {
        return count($this->chunks);
    }

    /**
     * The document name for chunk $n on the wire: sealed chunks are
     * addressed by their ciphertext hash (wire_sha256), raw chunks by
     * their plaintext hash.
     */
    public function chunkDocName(int $n): string
    {
        foreach ($this->chunks as $chunk) {
            if ($chunk['n'] === $n) {
                return isset($chunk['wire_sha256']) ? $chunk['wire_sha256'] : $chunk['sha256'];
            }
        }

        throw new RuntimeException("no chunk {$n} in manifest");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'version' => self::VERSION,
            'name' => $this->name,
            'size' => $this->size,
            'sha256' => $this->sha256,
            'mode' => $this->mode,
            'mime' => $this->mime,
            'created' => $this->created,
            'chunks' => $this->chunks,
        ];

        if ($this->saltHex !== null) {
            $data['salt'] = $this->saltHex;
        }
        if ($this->macHex !== null) {
            $data['mac'] = $this->macHex;
        }

        return $data;
    }

    /**
     * Chunk `n` must be exactly 0..count-1, ascending, with no gap and no
     * repeat. Enforced on BOTH construction and parse, because the chunk
     * list is the one part of the manifest every per-chunk verification
     * takes its expectations FROM.
     *
     * @param  list<mixed>  $chunks  deliberately UNTYPED rows: this runs on
     *                               data decoded from an editable chat message,
     *                               so the row shape is asserted here rather
     *                               than assumed by a PHPDoc
     */
    private static function assertChunkOrder(array $chunks): void
    {
        $position = 0;
        foreach ($chunks as $chunk) {
            $n = is_array($chunk) ? ($chunk['n'] ?? null) : null;
            if ($n !== $position) {
                throw new RuntimeException(
                    'manifest chunk order must be exactly 0..'.(count($chunks) - 1).' in ascending order; '
                    .'got n='.(is_int($n) ? $n : 'non-int').' at position '.$position
                    .' (a reordered, gapped or duplicated chunk list is refused, not tolerated)',
                );
            }
            $position++;
        }
    }

    private static function assertHex64(string $value, string $field): void
    {
        if (! self::isHex64($value)) {
            throw new InvalidArgumentException("{$field} must be exactly 64 lowercase hex chars");
        }
    }

    private static function isHex64(string $value): bool
    {
        return strlen($value) === 64 && ctype_xdigit($value) && strtolower($value) === $value;
    }
}
