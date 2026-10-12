<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use RuntimeException;

/**
 * Keyed manifest authentication for TStore (finding H4).
 *
 * WHY THIS EXISTS: the TFSTORE1 manifest is a plain chat message. Anyone
 * who can post in the store bucket can EDIT it, and the per-chunk
 * verifications in StoreService::fetchChunkPlain() take their expectations
 * FROM that same manifest — so an unsigned manifest is not merely
 * unauthenticated, it is self-certifying. Chunk-order canonicalisation
 * (ObjectManifest::assertChunkOrder) closes the reordering hole, and this
 * class closes the rest: a sealed object's manifest carries a MAC over its
 * own canonical body, and a single flipped field no longer assembles.
 *
 * WHY THE KEY IS THE OBJECT KEY: the only secret this module has is the
 * per-object key the caller already supplies to seal the chunks. Deriving
 * the MAC key from anything the channel audience ALSO holds — the channel
 * id, the access hash, a message id, a Telegram file id — reproduces
 * exactly the Backup\TelegramVault::manifestKey() defect (an unkeyed
 * BLAKE2b over a value the attacker is handed by Telegram itself), and a
 * MAC under such a key authenticates nothing at all. A key the adversary
 * has is not a key.
 *
 * Domain separation: the MAC is BLAKE2b(context || canonicalBody) under a
 * 32-byte key, so it can never collide with a chunk wire hash, a
 * plaintext content address, or the object key itself.
 *
 * Raw (keyless) objects get NO mac: with no secret there is nothing to
 * authenticate with, and a mac under a public constant would be a check
 * anyone can forge. Their guarantee is the whole-object sha256 comparison
 * in StoreService, which detects any inconsistency between the declared
 * hash and the assembled bytes.
 */
final class ManifestMac
{
    /** Domain separator; part of the security claim, never reuse it. */
    private const CONTEXT = 'teleframe-store:manifest-mac:v1:';

    private const HEX64_BYTES = 32;

    private function __construct()
    {
        // static-only: nothing to construct, nothing to keep.
    }

    /**
     * The manifest MAC (64 lowercase hex chars) over $body — an
     * ObjectManifest::toArray() payload. The `mac` field itself is
     * excluded from the covered bytes, so the MAC can be attached after
     * the fact (ObjectManifest::withMac) without being circular.
     *
     * The covered bytes are CANONICAL: a fixed key order, chunk keys in a
     * fixed order, and no `mac` — so the same manifest hashes identically
     * whether it arrives as a decoded JSON message (arbitrary key order) or
     * as a re-serialized toArray(). Without this the MAC would depend on
     * JSON key order, i.e. on how the message happened to be typed.
     *
     * @param  array<string, mixed>  $body
     */
    public static function compute(string $key, array $body): string
    {
        self::assertKey($key);

        $json = json_encode(self::canonical($body), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return bin2hex(sodium_crypto_generichash(self::CONTEXT.$json, $key, self::HEX64_BYTES));
    }

    /**
     * The canonical covered form: fixed key order, fixed chunk key order,
     * `mac` and `salt` present only when the manifest carries them.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function canonical(array $body): array
    {
        $chunks = [];
        $raw = $body['chunks'] ?? [];
        if (is_array($raw)) {
            foreach ($raw as $chunk) {
                if (! is_array($chunk)) {
                    continue;
                }
                $normal = [
                    'n' => (int) ($chunk['n'] ?? 0),
                    'size' => (int) ($chunk['size'] ?? 0),
                    'sha256' => (string) ($chunk['sha256'] ?? ''),
                ];
                if (isset($chunk['wire_sha256'])) {
                    $normal['wire_sha256'] = (string) $chunk['wire_sha256'];
                }
                $chunks[] = $normal;
            }
        }

        $canonical = [
            'version' => $body['version'] ?? null,
            'name' => $body['name'] ?? null,
            'size' => $body['size'] ?? null,
            'sha256' => $body['sha256'] ?? null,
            'mode' => $body['mode'] ?? null,
            'mime' => $body['mime'] ?? null,
        ];

        if (($body['salt'] ?? null) !== null) {
            $canonical['salt'] = (string) $body['salt'];
        }
        $canonical['created'] = $body['created'] ?? null;
        $canonical['chunks'] = $chunks;

        return $canonical;
    }

    /**
     * Recompute and compare. Constant-time via hash_equals, and the
     * message says which field is untrustworthy WITHOUT echoing any
     * covered value (a manifest may name object paths and a KDF salt).
     *
     * @param  array<string, mixed>  $body
     *
     * @throws RuntimeException when the MAC is absent or does not match
     */
    public static function verify(string $key, array $body, ?string $expected, string $label): void
    {
        if ($expected === null || $expected === '') {
            throw new RuntimeException(
                "{$label} carries no manifest MAC: an unauthenticated manifest is refused, "
                .'because its own chunk hashes are the only expectation the per-chunk checks have',
            );
        }

        $actual = self::compute($key, $body);

        if (! hash_equals($expected, $actual)) {
            throw new RuntimeException(
                "{$label} manifest MAC mismatch: the manifest was edited after it was written "
                .'(or was written under a different passphrase)',
            );
        }
    }

    private static function assertKey(string $key): void
    {
        if (strlen($key) !== 32) {
            throw new RuntimeException('manifest MAC key must be exactly 32 raw bytes, got '.strlen($key));
        }
    }
}
