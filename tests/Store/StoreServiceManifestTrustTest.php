<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * End-to-end manifest trust at the StoreService seam (finding H4).
 *
 * The attack these pin: the TFSTORE1 manifest is a plain chat message, so
 * anyone who can post in the bucket can EDIT it — and every per-chunk
 * check in fetchChunkPlain() takes its expectation FROM that same
 * manifest, which made the manifest self-certifying. Before the fix
 * get() concatenated the chunks and returned them: a hash that is
 * computed and never compared is decoration, so an edited manifest
 * returned "success" over bytes it did not describe.
 *
 * The manifest is injected through the truth row (MemoryStoreIndex), which
 * is exactly where manifestFor() reads it from first.
 */
final class StoreServiceManifestTrustTest extends TestCase
{
    private const BUCKET = 'media-pool';

    private const PASSPHRASE = 'correct horse battery staple';

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private StoreService $service;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'trust');
        $this->index = new MemoryStoreIndex;
        $this->service = new StoreService(
            accountId: 7,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 4 * 1024 * 1024),
            queue: new SyncUploadQueue,
        );
    }

    /** Put a real object and hand back its manifest array. */
    private function put(string $name, string $bytes, string $mode = ObjectManifest::MODE_RAW, ?string $passphrase = null): array
    {
        return $this->service->put(self::BUCKET, $name, $bytes, $mode, $passphrase)['manifest'];
    }

    /** Overwrite the truth row with an edited manifest — the attacker's write. */
    private function forge(string $name, array $manifest): void
    {
        $this->index->upsertObject(7, self::BUCKET, $name, [
            'mode' => $manifest['mode'],
            'size' => $manifest['size'],
            'chunks' => $manifest['chunks'],
            'metadata' => $manifest,
            'created_at_unix' => 0,
            'updated_at_unix' => 0,
        ]);
    }

    public function test_an_untouched_object_still_roundtrips(): void
    {
        $bytes = str_repeat('trust me ', 1_000);
        $this->put('ok.bin', $bytes);

        $this->assertSame($bytes, $this->service->get(self::BUCKET, 'ok.bin'));
    }

    public function test_a_manifest_whose_declared_hash_was_edited_is_refused(): void
    {
        $bytes = str_repeat('a', 1_000);
        $manifest = $this->put('edited.bin', $bytes);

        // One field changed. Every per-chunk hash still matches, because
        // the chunk list is untouched — this is the corruption that used
        // to return the wrong bytes as a success.
        $manifest['sha256'] = hash('sha256', 'something else entirely');
        $this->forge('edited.bin', $manifest);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('whole-object sha256 mismatch');

        $this->service->get(self::BUCKET, 'edited.bin');
    }

    public function test_a_manifest_whose_declared_size_was_edited_is_refused(): void
    {
        $manifest = $this->put('resized.bin', str_repeat('b', 1_000));

        $manifest['size'] = 12;
        $this->forge('resized.bin', $manifest);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('assembled');

        $this->service->get(self::BUCKET, 'resized.bin');
    }

    public function test_a_manifest_whose_final_chunk_was_dropped_is_refused(): void
    {
        // Two real one-chunk objects; a manifest that claims the
        // concatenation but lists only the FIRST chunk. The chunk list
        // stays canonical (0..0) — so this is caught by the whole-object
        // comparison, which is exactly why the hash must be compared.
        $first = str_repeat('first-part-', 100);
        $second = str_repeat('second-part-', 100);
        $a = $this->put('part-a.bin', $first);
        $b = $this->put('part-b.bin', $second);

        $whole = $first.$second;
        $dropped = [
            'version' => ObjectManifest::VERSION,
            'name' => 'concat.bin',
            'size' => strlen($whole),
            'sha256' => hash('sha256', $whole),
            'mode' => ObjectManifest::MODE_RAW,
            'mime' => 'application/octet-stream',
            'created' => $a['created'],
            'chunks' => [$a['chunks'][0]],
        ];
        self::assertSame(1, count($dropped['chunks']), 'the trailing chunk is gone');
        self::assertNotSame($b['chunks'][0]['sha256'], $dropped['chunks'][0]['sha256']);
        $this->forge('concat.bin', $dropped);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('assembled');

        $this->service->get(self::BUCKET, 'concat.bin');
    }

    public function test_a_manifest_whose_chunk_order_was_swapped_is_refused_at_parse(): void
    {
        $first = str_repeat('first-part-', 100);
        $second = str_repeat('second-part-', 100);
        $a = $this->put('ord-a.bin', $first);
        $b = $this->put('ord-b.bin', $second);

        $whole = $first.$second;
        $swapped = [
            'version' => ObjectManifest::VERSION,
            'name' => 'concat.bin',
            'size' => strlen($whole),
            'sha256' => hash('sha256', $whole),
            'mode' => ObjectManifest::MODE_RAW,
            'mime' => 'application/octet-stream',
            'created' => $a['created'],
            'chunks' => [
                $b['chunks'][0] + ['n' => 0], // same set, swapped n
                $a['chunks'][0] + ['n' => 1],
            ],
        ];
        $this->forge('concat.bin', $swapped);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chunk order must be exactly 0..1');

        $this->service->get(self::BUCKET, 'concat.bin');
    }

    // ── sealed objects: the MAC ───────────────────────────────────────

    public function test_a_sealed_manifest_is_written_with_a_mac_and_verifies(): void
    {
        $bytes = str_repeat('sealed trust ', 500);
        $manifest = $this->put('secret.db', $bytes, ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        self::assertIsString($manifest['mac'] ?? null, 'a sealed manifest must carry a MAC');
        self::assertSame(64, strlen((string) $manifest['mac']));
        self::assertSame($bytes, $this->service->get(self::BUCKET, 'secret.db', self::PASSPHRASE));
    }

    public function test_a_raw_manifest_carries_no_mac(): void
    {
        $manifest = $this->put('plain.bin', str_repeat('c', 500));

        self::assertArrayNotHasKey('mac', $manifest, 'raw objects have no key, so a MAC would authenticate nothing');
    }

    public function test_a_sealed_manifest_edited_after_the_fact_is_refused(): void
    {
        $bytes = str_repeat('sealed trust ', 500);
        $manifest = $this->put('secret.db', $bytes, ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        $manifest['size'] = 4;
        $manifest['sha256'] = hash('sha256', 'nope');
        $this->forge('secret.db', $manifest);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('manifest MAC mismatch');

        $this->service->get(self::BUCKET, 'secret.db', self::PASSPHRASE);
    }

    public function test_a_sealed_manifest_with_its_mac_stripped_is_refused(): void
    {
        $bytes = str_repeat('sealed trust ', 500);
        $manifest = $this->put('secret.db', $bytes, ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        unset($manifest['mac']);
        $this->forge('secret.db', $manifest);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('carries no manifest MAC');

        $this->service->get(self::BUCKET, 'secret.db', self::PASSPHRASE);
    }

    public function test_a_sealed_manifest_written_under_another_passphrase_does_not_decrypt(): void
    {
        $bytes = str_repeat('sealed trust ', 500);
        $this->put('secret.db', $bytes, ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        $this->expectException(RuntimeException::class);

        $this->service->get(self::BUCKET, 'secret.db', 'a different passphrase');
    }

    public function test_a_sealed_manifest_cannot_be_read_without_a_passphrase(): void
    {
        $this->put('secret.db', str_repeat('sealed trust ', 500), ObjectManifest::MODE_SEALED, self::PASSPHRASE);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires a passphrase');

        $this->service->get(self::BUCKET, 'secret.db');
    }

    // ── verify() agrees with get() ─────────────────────────────────────

    public function test_verify_reports_the_whole_object_mismatch_rather_than_ok(): void
    {
        $manifest = $this->put('edited.bin', str_repeat('d', 1_000));
        $manifest['sha256'] = hash('sha256', 'not this');
        $this->forge('edited.bin', $manifest);

        $report = $this->service->verify(self::BUCKET, 'edited.bin');

        self::assertFalse($report['ok'], 'a verifier that says ok on mismatched bytes is worse than none');
        self::assertStringContainsString('object: ', implode("\n", $report['errors']));
    }

    public function test_verify_reports_a_sealed_manifest_mac_failure(): void
    {
        $manifest = $this->put('secret.db', str_repeat('e', 1_000), ObjectManifest::MODE_SEALED, self::PASSPHRASE);
        $manifest['mime'] = 'text/plain';
        $this->forge('secret.db', $manifest);

        $report = $this->service->verify(self::BUCKET, 'secret.db', self::PASSPHRASE);

        self::assertFalse($report['ok']);
        self::assertStringContainsString('manifest: ', implode("\n", $report['errors']));
    }

    public function test_verify_is_ok_for_an_untouched_object(): void
    {
        $this->put('ok.bin', str_repeat('f', 1_000));

        $report = $this->service->verify(self::BUCKET, 'ok.bin');

        self::assertTrue($report['ok'], implode("\n", $report['errors']));
        self::assertSame(1, $report['chunks']);
    }
}
