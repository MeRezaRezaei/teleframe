<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\MetadataMessage;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A COLD COPY: no local file, no local index row, same Telegram (V1.2,
 * 2026-09-27 — the "84/100" deduction: "a cold copy still depends on the local
 * index and the local file until the reclamation policy is switched").
 *
 * The fixture is a cold copy in the only sense that matters and in none of the
 * senses that would make the test easy: ONE wire (the FakeStoreApi) is written
 * once through a retaining service, and then a SECOND service — a different
 * index object, an empty one, as if the database had been rebuilt on another
 * machine — is asked to read the same Telegram. Nothing is shared between them
 * but the channel. So the assertions cannot pass on local state, because there
 * is no local state to pass on.
 *
 * THE BRANCH IS INJECTED, NOT INHERITED: the cold behaviour is reached through
 * ReferencePolicy::coldRemoteAuthoritative() and RemoteTierContract, both
 * constructor arguments. Nothing here asks the host whether Telegram is
 * reachable, whether ext-grpc exists, or whether a session string is set — the
 * same property commits ee21e334 and 97413884 were written about, and the same
 * one that makes these assertions hold in a CI image with no Telegram at all.
 *
 * The safety arms are the point of the file, not decoration: a cold read must
 * not become a WEAKER read. A manifest edited in the channel is still refused
 * (whole-object sha256 for raw, MAC for sealed), a foreign message shadowing
 * the marker is refused rather than tried-and-skipped, and a read that already
 * has a local truth row is NOT redirected to the editable channel copy.
 */
final class ColdCopyReadTest extends TestCase
{
    private const ACCOUNT = 11;

    private const BUCKET = 'data:cold';

    private const PASSPHRASE = 'cold copy passphrase';

    private FakeStoreApi $api;

    /** The index the seeding (warm) service wrote. Not the cold one. */
    private MemoryStoreIndex $warmIndex;

    /** The cold machine's index: starts EMPTY and is only ever filled by the remote. */
    private MemoryStoreIndex $coldIndex;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'cold-read');
        $this->warmIndex = new MemoryStoreIndex;
        $this->coldIndex = new MemoryStoreIndex;
    }

    /** A service over $index with the given posture. No cache sharing either. */
    private function service(MemoryStoreIndex $index, ReferencePolicy $policy): StoreService
    {
        return new StoreService(
            accountId: self::ACCOUNT,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $this->api->apiMap()),
            index: $index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 4 * 1024 * 1024),
            queue: new SyncUploadQueue,
            referencePolicy: $policy,
        );
    }

    private function cold(): StoreService
    {
        return $this->service($this->coldIndex, ReferencePolicy::coldRemoteAuthoritative());
    }

    private function retaining(): StoreService
    {
        return $this->service($this->coldIndex, ReferencePolicy::retainLocal());
    }

    /**
     * Put an object on the wire through the warm service. The bytes are handed
     * over as a STRING, so the seeding never writes a local file either: there
     * is no local file anywhere in this test, by construction rather than by
     * assertion.
     */
    private function seed(string $name, string $bytes, string $mode = ObjectManifest::MODE_RAW, ?string $passphrase = null): void
    {
        $this->service($this->warmIndex, ReferencePolicy::retainLocal())
            ->put(self::BUCKET, $name, $bytes, $mode, $passphrase);
    }

    /** The one fixture every cold read depends on: a row on Telegram, none locally. */
    private function assertColdPrecondition(string $name): void
    {
        self::assertNotNull(
            $this->warmIndex->findObject(self::ACCOUNT, self::BUCKET, $name),
            'the warm service did index the object, or the fixture is broken',
        );
        self::assertNull(
            $this->coldIndex->findObject(self::ACCOUNT, self::BUCKET, $name),
            'precondition: the cold machine has no local row for this object',
        );
    }

    /**
     * Rewrite the manifest BODY of a stored TFSTORE1 message on the wire — the
     * attacker's edit. Patches the flat log AND every peer partition, because
     * the fake copies rows by value into the partitions and a search reads the
     * partition. $mutate receives the decoded manifest and returns it changed.
     */
    private function editManifestMessage(string $name, callable $mutate): void
    {
        $target = MetadataMessage::MARKER.' '.$name;
        $patch = function (array &$row) use ($target, $mutate): bool {
            if (($row['name'] ?? null) !== $target || $row['doc_id'] !== null) {
                return false;
            }
            $text = (string) $row['text'];
            $start = (int) strpos($text, '{');
            $end = (int) strrpos($text, '}');
            self::assertGreaterThan($start, $end, 'the stored message carries no JSON body to edit');
            $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            self::assertIsArray($decoded);
            $edited = $mutate($decoded);
            self::assertIsArray($edited);
            $row['text'] = $target."\n".MetadataMessage::FENCE."\n"
                .json_encode($edited, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n"
                .MetadataMessage::FENCE_END;

            return true;
        };

        foreach ($this->api->messages as $i => $row) {
            $copy = $row;
            if ($patch($copy)) {
                $this->api->messages[$i] = $copy;
            }
        }
        foreach ($this->api->peerMessages as $peerId => $rows) {
            foreach ($rows as $i => $row) {
                $copy = $row;
                if ($patch($copy)) {
                    $this->api->peerMessages[$peerId][$i] = $copy;
                }
            }
        }
    }

    /**
     * Post a text message INTO the bucket's own channel, the way another member
     * of the channel would. The fake partitions its wire by peer, so a message
     * left in its default partition would be invisible to a search of the
     * bucket's channel — i.e. the shadow would never be a shadow at all.
     */
    private function postIntoBucket(string $text): int
    {
        $channel = $this->api->channels[TelegramStoreChannel::channelTitle(self::BUCKET)] ?? null;
        self::assertIsArray($channel, 'the bucket channel was never created, so the fixture is broken');
        $this->api->seedPeerId = (int) $channel['id'];

        return $this->api->sendText($text);
    }

    // ── the read itself ────────────────────────────────────────────────

    public function test_a_cold_copy_with_no_local_row_serves_the_bytes_from_telegram_alone(): void
    {
        $bytes = str_repeat('cold bytes ', 500);
        $this->seed('cold.bin', $bytes);
        $this->assertColdPrecondition('cold.bin');

        $cold = $this->cold();
        $uploadsBefore = $this->api->uploadPartCalls;

        self::assertSame($bytes, $cold->get(self::BUCKET, 'cold.bin'));
        self::assertSame($uploadsBefore, $this->api->uploadPartCalls, 'a cold read downloads; it must never upload');
        self::assertNotNull(
            $this->coldIndex->findObject(self::ACCOUNT, self::BUCKET, 'cold.bin'),
            'the remote answer is written back as a cache, so the next read is local',
        );
    }

    public function test_a_cold_copy_reports_exists_and_size_without_a_local_row(): void
    {
        $this->seed('sized.bin', str_repeat('x', 4_096));
        $this->assertColdPrecondition('sized.bin');

        $cold = $this->cold();

        self::assertTrue($cold->exists(self::BUCKET, 'sized.bin'));
        self::assertSame(4_096, $cold->size(self::BUCKET, 'sized.bin'));
    }

    public function test_a_cold_sealed_object_still_needs_its_passphrase(): void
    {
        $bytes = str_repeat('sealed cold ', 400);
        $this->seed('secret.db', $bytes, ObjectManifest::MODE_SEALED, self::PASSPHRASE);
        $this->assertColdPrecondition('secret.db');

        self::assertSame($bytes, $this->cold()->get(self::BUCKET, 'secret.db', self::PASSPHRASE));

        // A fresh cold index, so the read above cannot have cached anything for
        // the no-passphrase arm.
        $this->coldIndex = new MemoryStoreIndex;
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires a passphrase');
        $this->cold()->get(self::BUCKET, 'secret.db');
    }

    /**
     * The retaining postures must not silently become cold: a local miss is
     * still the answer they report, and they must pay NOTHING for it. The
     * default install does not walk a channel to discover its own emptiness.
     */
    public function test_the_retaining_posture_does_not_pay_for_a_remote_lookup(): void
    {
        $this->seed('kept.bin', str_repeat('k', 1_000));
        $this->assertColdPrecondition('kept.bin');

        $retaining = $this->retaining();
        $searchesBefore = count($this->api->searches);

        self::assertFalse($retaining->exists(self::BUCKET, 'kept.bin'));
        self::assertNull($retaining->size(self::BUCKET, 'kept.bin'));
        self::assertCount($searchesBefore, $this->api->searches, 'the default posture must not go looking on the wire');
    }

    public function test_a_cold_copy_lists_what_the_bucket_holds_from_the_remote_tier(): void
    {
        $this->seed('one.bin', str_repeat('1', 10));
        $this->seed('two.bin', str_repeat('2', 20));

        $rows = $this->cold()->listRemoteObjects(self::BUCKET);

        $byName = [];
        foreach ($rows as $row) {
            $byName[$row['name']] = $row;
        }
        self::assertArrayHasKey('one.bin', $byName);
        self::assertArrayHasKey('two.bin', $byName);
        self::assertSame(20, $byName['two.bin']['size']);
        self::assertSame(hash('sha256', str_repeat('2', 20)), $byName['two.bin']['sha256']);
        self::assertSame(ObjectManifest::MODE_RAW, $byName['two.bin']['mode']);
    }

    // ── the safety arms: a cold read must not be a weaker read ─────────

    public function test_a_raw_manifest_edited_in_the_channel_is_still_refused_when_cold(): void
    {
        $this->seed('edited.bin', str_repeat('a', 1_000));
        $this->assertColdPrecondition('edited.bin');

        // The attack: rewrite the whole-object hash in the manifest message.
        // Every per-chunk hash still matches, because the chunk list is
        // untouched — only the object-level comparison catches this.
        $this->editManifestMessage('edited.bin', function (array $manifest): array {
            $manifest['sha256'] = hash('sha256', 'something else entirely');

            return $manifest;
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('whole-object sha256 mismatch');

        $this->cold()->get(self::BUCKET, 'edited.bin');
    }

    public function test_a_sealed_manifest_edited_in_the_channel_is_still_refused_when_cold(): void
    {
        $this->seed('secret.db', str_repeat('sealed cold ', 400), ObjectManifest::MODE_SEALED, self::PASSPHRASE);
        $this->assertColdPrecondition('secret.db');

        $this->editManifestMessage('secret.db', function (array $manifest): array {
            $manifest['size'] = 4;
            $manifest['sha256'] = hash('sha256', 'nope');

            return $manifest;
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('manifest MAC mismatch');

        $this->cold()->get(self::BUCKET, 'secret.db', self::PASSPHRASE);
    }

    /**
     * A NEWER message wearing the same marker, posted by anyone with write
     * access to the bucket. The cold read must REFUSE it — not try it, fail,
     * and quietly fall through to the older genuine manifest, because
     * "try candidates until one verifies" is a plant-and-probe oracle.
     */
    public function test_a_foreign_message_shadowing_the_manifest_is_refused_not_served(): void
    {
        $this->seed('shadowed.bin', str_repeat('real ', 200));
        $this->assertColdPrecondition('shadowed.bin');

        $this->postIntoBucket("TFSTORE1: shadowed.bin\nnot a manifest at all");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has no JSON fence');

        $this->cold()->get(self::BUCKET, 'shadowed.bin');
    }

    /**
     * The other half of the same property, and the one that keeps this slice
     * from widening anything: a read that HAS a local truth row is not
     * redirected to the channel's editable copy. The shadow above cannot
     * hijack a row-backed read, so nothing that could not read an object
     * before this change can read it now.
     */
    public function test_a_shadowing_message_cannot_hijack_a_read_that_has_a_local_row(): void
    {
        $bytes = str_repeat('real ', 200);
        $this->seed('rowed.bin', $bytes);
        $this->postIntoBucket("TFSTORE1: rowed.bin\nnot a manifest at all");

        // The warm service holds the row, the cold one does not: the same wire,
        // two answers, and the row-backed one is unchanged by the shadow.
        self::assertSame($bytes, $this->service($this->warmIndex, ReferencePolicy::retainLocal())->get(self::BUCKET, 'rowed.bin'));
    }

    public function test_a_cold_read_of_an_unknown_object_still_fails_loudly(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('object not found');

        $this->cold()->get(self::BUCKET, 'never-stored.bin');
    }

    public function test_a_cold_read_is_verifiable_with_verify_too(): void
    {
        $this->seed('checked.bin', str_repeat('v', 2_000));
        $this->assertColdPrecondition('checked.bin');

        $report = $this->cold()->verify(self::BUCKET, 'checked.bin');

        self::assertTrue($report['ok'], implode("\n", $report['errors']));
        self::assertSame(1, $report['chunks']);
    }
}
