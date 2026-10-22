<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\ManifestMac;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Manifest trust (finding H4) — the manifest is a plain chat message
 * anyone who can post in the bucket can EDIT, so these are the parse-time
 * and MAC-time rules that make "well-formed JSON" a non-event.
 *
 * Before the fix, none of the order assertions existed: fromArray()
 * accepted any well-formed chunk list, so an attacker could reorder `n`
 * values (every per-chunk hash still matched, because the expectations
 * come from the same unsigned manifest), drop the trailing chunk, or
 * duplicate an entry — and get() reported success over the wrong bytes.
 */
final class ManifestTrustTest extends TestCase
{
    private const SHA_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const SHA_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private const SHA_C = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

    private const WIRE_A = '1111111111111111111111111111111111111111111111111111111111111111';

    private const WIRE_B = '2222222222222222222222222222222222222222222222222222222222222222';

    private const WIRE_C = '3333333333333333333333333333333333333333333333333333333333333333';

    /**
     * @param  list<array{n: int, size: int, sha256: string, wire_sha256?: string}>  $chunks
     * @return array<string, mixed>
     */
    private static function sealedBody(array $chunks, string $wholeHash = self::SHA_A): array
    {
        return [
            'version' => ObjectManifest::VERSION,
            'name' => 'secret.db',
            'size' => 30,
            'sha256' => $wholeHash,
            'mode' => ObjectManifest::MODE_SEALED,
            'mime' => 'application/octet-stream',
            'salt' => str_repeat('ab', 16),
            'created' => '2026-09-26T00:00:00+00:00',
            'chunks' => $chunks,
        ];
    }

    /** @return list<array{n: int, size: int, sha256: string, wire_sha256: string}> */
    private static function threeChunks(): array
    {
        return [
            ['n' => 0, 'size' => 10, 'sha256' => self::SHA_A, 'wire_sha256' => self::WIRE_A],
            ['n' => 1, 'size' => 10, 'sha256' => self::SHA_B, 'wire_sha256' => self::WIRE_B],
            ['n' => 2, 'size' => 10, 'sha256' => self::SHA_C, 'wire_sha256' => self::WIRE_C],
        ];
    }

    // ── chunk order: refused at PARSE time, not tolerated ──────────────

    public function test_reordered_chunk_numbers_are_refused(): void
    {
        $chunks = self::threeChunks();
        [$chunks[0], $chunks[1]] = [$chunks[1], $chunks[0]]; // swap the first two `n`

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chunk order must be exactly 0..2');

        ObjectManifest::fromArray(self::sealedBody($chunks));
    }

    public function test_a_dropped_trailing_chunk_is_a_shorter_object_not_an_order_violation(): void
    {
        // Dropping the LAST chunk leaves a perfectly canonical 0..1 list —
        // order canonicalisation cannot see it, and must not pretend to.
        // What catches it is the whole-object sha256/size comparison in
        // StoreService::get() (see StoreServiceTest), because the manifest
        // still declares the 3-chunk object's hash and size.
        $chunks = self::threeChunks();
        array_pop($chunks);

        $manifest = ObjectManifest::fromArray(self::sealedBody($chunks));

        $this->assertSame(2, $manifest->chunkCount());
        $this->assertSame([0, 1], array_column($manifest->chunks(), 'n'));
    }

    public function test_a_chunk_list_that_starts_at_one_is_refused(): void
    {
        $chunks = self::threeChunks();
        $chunks[0]['n'] = 1;
        $chunks[1]['n'] = 2;
        $chunks[2]['n'] = 3;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chunk order must be exactly 0..2');

        ObjectManifest::fromArray(self::sealedBody($chunks));
    }

    public function test_duplicated_chunk_number_is_refused(): void
    {
        $chunks = self::threeChunks();
        $chunks[2]['n'] = 1; // n = 0, 1, 1

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chunk order must be exactly 0..2');

        ObjectManifest::fromArray(self::sealedBody($chunks));
    }

    public function test_a_reorder_that_keeps_the_set_is_still_refused(): void
    {
        // The nastiest shape: 0,2,1 — every `n` present exactly once, so a
        // "did you see all of them" check would pass it.
        $chunks = self::threeChunks();
        $chunks[1]['n'] = 2;
        $chunks[2]['n'] = 1;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chunk order must be exactly 0..2');

        ObjectManifest::fromArray(self::sealedBody($chunks));
    }

    public function test_out_of_order_chunks_are_refused_on_construction_too(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chunk order must be exactly 0..1');

        ObjectManifest::new(
            'x.bin',
            20,
            self::SHA_A,
            ObjectManifest::MODE_RAW,
            'application/octet-stream',
            null,
            [
                ['n' => 1, 'size' => 10, 'sha256' => self::SHA_A],
                ['n' => 0, 'size' => 10, 'sha256' => self::SHA_B],
            ],
        );
    }

    public function test_canonical_chunk_order_still_parses(): void
    {
        $manifest = ObjectManifest::fromArray(self::sealedBody(self::threeChunks()));

        $this->assertSame(3, $manifest->chunkCount());
        $this->assertSame([0, 1, 2], array_column($manifest->chunks(), 'n'));
    }

    // ── the MAC ───────────────────────────────────────────────────────

    public function test_mac_roundtrips_over_the_manifest_body(): void
    {
        $body = self::sealedBody(self::threeChunks());
        $key = random_bytes(32);
        $body['mac'] = ManifestMac::compute($key, $body);

        $manifest = ObjectManifest::fromArray($body);

        $this->assertSame($body['mac'], $manifest->macHex());
        ManifestMac::verify($key, $manifest->toArray(), $manifest->macHex(), 'object secret.db');
    }

    public function test_a_manifest_edited_after_the_mac_was_written_does_not_verify(): void
    {
        $body = self::sealedBody(self::threeChunks());
        $key = random_bytes(32);
        $mac = ManifestMac::compute($key, $body);
        $body['mac'] = $mac;

        // The edit that used to pass every per-chunk check: same chunks,
        // same hashes, but the object the manifest describes is now a
        // different size/hash pair.
        $body['size'] = 999;
        $body['sha256'] = self::SHA_C;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('manifest MAC mismatch');

        ManifestMac::verify($key, $body, $mac, 'object secret.db');
    }

    public function test_a_manifest_edited_before_the_mac_field_was_stripped_does_not_verify(): void
    {
        $body = self::sealedBody(self::threeChunks());
        $key = random_bytes(32);
        $mac = ManifestMac::compute($key, $body);

        // Forgery shape: keep the old mac, swap a chunk hash. compute()
        // unsets `mac`, so an attacker cannot launder the change through it.
        $forged = $body;
        $forged['mac'] = $mac;
        $forged['chunks'][0]['sha256'] = self::SHA_C;

        $this->assertNotSame($mac, ManifestMac::compute($key, $forged));

        $this->expectException(RuntimeException::class);
        ManifestMac::verify($key, $forged, $mac, 'object secret.db');
    }

    public function test_a_manifest_with_no_mac_is_refused_not_defaulted(): void
    {
        $body = self::sealedBody(self::threeChunks());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('carries no manifest MAC');

        ManifestMac::verify(random_bytes(32), $body, $body['mac'] ?? null, 'object secret.db');
    }

    public function test_a_manifest_sealed_under_a_different_passphrase_does_not_verify(): void
    {
        $body = self::sealedBody(self::threeChunks());
        $body['mac'] = ManifestMac::compute(str_repeat('k', 32), $body);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('manifest MAC mismatch');

        ManifestMac::verify(random_bytes(32), $body, $body['mac'], 'object secret.db');
    }

    public function test_a_public_looking_key_is_not_accepted_as_a_mac_key_shape(): void
    {
        // The H5 shape — a value the channel audience already holds — is
        // not what the MAC key is; the key is the caller's derived object
        // key, 32 raw bytes, and a wrong-sized one is a hard error rather
        // than a silently weakened hash.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exactly 32 raw bytes');

        ManifestMac::compute('teleframe-backup:vault-manifest:v1:4242', self::sealedBody(self::threeChunks()));
    }

    public function test_a_non_hex_mac_field_is_refused_at_parse(): void
    {
        $body = self::sealedBody(self::threeChunks());
        $body['mac'] = 'not-hex';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('manifest mac requires hex64');

        ObjectManifest::fromArray($body);
    }

    public function test_with_mac_does_not_cover_its_own_field(): void
    {
        $key = random_bytes(32);
        $manifest = ObjectManifest::fromArray(self::sealedBody(self::threeChunks()));
        $mac = ManifestMac::compute($key, $manifest->toArray());
        $attached = $manifest->withMac($mac);

        $this->assertSame($mac, $attached->macHex());
        $this->assertNull($manifest->macHex(), 'withMac is immutable, not a mutation');
        ManifestMac::verify($key, $attached->toArray(), $attached->macHex(), 'object secret.db');
    }
}
