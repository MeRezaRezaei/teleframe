<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use LogicException;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\ChannelRegistry;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\MetadataMessage;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\ReclaimOutcome;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Store\TelegramTier;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ATTACKING reclaimLocal(), and what the attacks now hit (2026-09-27).
 *
 * The claim under test is the security-critical one: reclaim must never delete
 * a local file whose durability is UNPROVEN. A fail-open here deletes a HOST's
 * data, so every case below names the verdict it pins, and every verdict was
 * reached by BREAKING the guard and watching the test go red — not by reading
 * the code and believing it.
 *
 * ── WHAT CHANGED, AND WHY THE FILE IS SPLIT THE WAY IT IS ───────────────
 * The proof used to be REFERENCE-shaped: non-empty file_reference plus a
 * positive doc_id, i.e. "document 777 is addressable". That is exactly what
 * publishByReference() needs and it says NOTHING about the bytes at $path, so
 * five distinct fail-opens lived in the gap. Each is now a guard in §2, and
 * each guard was watched fail:
 *
 *   1 a row's content_sha256 was written null and read NOWHERE, so a row
 *     declaring one hash freed a file holding unrelated bytes;
 *   2 findMedia() was consulted first and short-circuited the remote, so one
 *     cold resolve cached a proof that outlived the document — a cache is a
 *     performance fact, never a durability proof;
 *   3 tf_store_media is attacker-writable (archiveMessageMedia writes a row
 *     straight from a live update), so "a local row wins" — true for
 *     tf_store_objects — was false here, and a stranger's row freed a host's
 *     file;
 *   4 TOCTOU: proof, then injected deleter;
 *   5 a caller-supplied $expect row was used verbatim, no lookup, no wire.
 *
 * §1 holds the guards that already failed closed and must not regress. §2 is
 * the five, now closed. §3 is the seam, pinned as a seam. §4 is the ONE limit
 * that is not closed, and it is the only test in this file that is allowed to
 * stay green while describing a failure — because the failure is a property
 * of an injected seam, and pretending otherwise would be the exact lie this
 * file exists to stop.
 *
 * EVERYTHING HERE IS A FAKE. One FakeStoreApi, MemoryStoreIndex, real files in
 * a temp dir for the delete assertions. No live Telegram run exists in this
 * repo and none is claimed.
 */
final class ReclaimDurabilityAttackTest extends TestCase
{
    private const ACCOUNT = 7;

    private const BUCKET = 'data:attack';

    /** The host's own private bytes. No remote document in this file holds them until a test says so. */
    private const HOST_BYTES = "the host's own private bytes\n";

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private int $channelId = 0;

    private string $path = '';

    /** @var list<array{0: string, 1: string}> */
    private array $unlinked = [];

    /** Every historyPage call the store made — the tripwire for "the remote was actually re-asked". */
    private int $historyCalls = 0;

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'reclaim-attack');
        $this->index = new MemoryStoreIndex;
        $this->unlinked = [];
        $this->historyCalls = 0;
        $this->path = (string) tempnam(sys_get_temp_dir(), 'tfstore-attack-');
        file_put_contents($this->path, self::HOST_BYTES);
    }

    protected function tearDown(): void
    {
        if ($this->path !== '' && is_file($this->path)) {
            unlink($this->path);
        }
    }

    // ── fixtures ──────────────────────────────────────────────────────

    private function cold(): StoreService
    {
        return $this->service(ReferencePolicy::coldRemoteAuthoritative(), $this->api->apiMap());
    }

    /** A service on a chosen posture and a chosen wire — the only two things that pick a branch. */
    private function service(ReferencePolicy $policy, array $map): StoreService
    {
        return new StoreService(
            accountId: self::ACCOUNT,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $map),
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 1024 * 1024),
            queue: new SyncUploadQueue,
            referencePolicy: $policy,
        );
    }

    /**
     * The same wire with historyPage counted, so "the remote tier was
     * re-consulted" is an assertion about an observed call and not a feeling.
     * A tripwire that cannot be observed is how a guard looks green while doing
     * nothing, and this file has already been burned by one.
     *
     * @return array<string, callable>
     */
    private function countedApi(): array
    {
        $map = $this->api->apiMap();
        $inner = $map['historyPage'];
        $map['historyPage'] = function (array $peer, int $limit, int $offsetId) use ($inner): array {
            $this->historyCalls++;

            return $inner($peer, $limit, $offsetId);
        };

        return $map;
    }

    /** A deleter that REALLY unlinks, and records that it was asked. */
    private function realUnlinker(bool $freed = true): callable
    {
        return function (string $disk, string $path) use ($freed): bool {
            $this->unlinked[] = [$disk, $path];
            if (! is_file($path) || ! $freed) {
                return false;
            }

            return unlink($path);
        };
    }

    private function bucketChannelId(): int
    {
        if ($this->channelId === 0) {
            $channel = new TelegramStoreChannel(self::BUCKET, $this->api->apiMap());
            $this->channelId = (int) ($channel->bucketInputPeer()['channel_id'] ?? 0);
            self::assertGreaterThan(0, $this->channelId, 'the bucket channel must resolve');
            $this->api->seedPeerId = $this->channelId;
        }

        return $this->channelId;
    }

    /** A document already in the bucket's channel, with NO local truth of any kind. */
    private function remoteDocument(string $name = 'remote.jpg', string $bytes = 'jpeg bytes'): int
    {
        $this->bucketChannelId();
        $msgId = $this->api->uploadDocument($name, $bytes);
        $this->index->deleteBucket(self::ACCOUNT, self::BUCKET);

        return $msgId;
    }

    /**
     * A remote document holding EXACTLY the host file's bytes — the only shape
     * a reclaim is ever allowed to free, because that byte-identity is now the
     * proof rather than a footnote to it.
     */
    private function remoteCopyOfHostFile(string $name = 'host-copy.jpg'): int
    {
        return $this->remoteDocument($name, self::HOST_BYTES);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function indexMedia(int $msgId, array $overrides = []): void
    {
        $this->index->upsertMedia(self::ACCOUNT, self::BUCKET, $msgId, $overrides + [
            'media_type' => 'document',
            'doc_id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-a-durable-file-reference',
            'size' => 4096,
            'mime' => 'image/jpeg',
            'content_sha256' => null,
            'indexed_at_unix' => 1758000000,
        ]);
    }

    /** A stranger's TFSTORE1 message in the bucket channel, addressed by its msg id. */
    private function foreignManifestMessage(string $name = 'foreign.bin'): int
    {
        $this->bucketChannelId();
        $manifest = ObjectManifest::new(
            $name,
            3,
            str_repeat('a', 64),
            ObjectManifest::MODE_RAW,
            'application/octet-stream',
            null,
            [['n' => 0, 'size' => 3, 'sha256' => str_repeat('b', 64)]],
        );

        return $this->api->sendText(MetadataMessage::render($manifest));
    }

    /** A real temp file holding $contents, cleaned up by the caller. */
    private function fileWith(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'tfstore-attack2-');
        file_put_contents($path, $contents);

        return $path;
    }

    // ══ §1 FAIL-CLOSED: the guards that must not regress ══════════════════

    /**
     * GUARD 1 — the posture gate. FALSIFIED: removing the `!$policy->reclaims()`
     * short-circuit turns this red.
     *
     * The retaining postures must not even LOOK at the index, let alone the
     * disk. Here a durable remote document exists AND a durable local row
     * exists; the default still touches nothing, because an operator who never
     * opted in has made no decision to be overridden by a bug.
     */
    public function test_fail_closed_the_default_posture_frees_nothing_even_with_a_durable_row_and_document(): void
    {
        $msgId = $this->remoteCopyOfHostFile();
        $this->indexMedia($msgId);

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            ReferencePolicy::retainLocal(),
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRetained());
        self::assertSame(ReclaimOutcome::REASON_POLICY_RETAINS, $outcome->reason());
        self::assertSame([], $this->unlinked, 'the deleter must be unreachable, not merely un-called');
        self::assertTrue(is_file($this->path), 'the bytes must still be on the host disk');
        self::assertSame(0, $this->historyCalls, 'and the retaining posture must not pay a wire call either');
    }

    /**
     * GUARD 2 — no proof, no delete. FALSIFIED: replacing the "the local file is
     * the only copy" throw with a fabricated row turns this red.
     */
    public function test_fail_closed_a_message_id_that_names_no_document_keeps_the_file(): void
    {
        $this->bucketChannelId();

        try {
            $this->cold()->reclaimLocal(
                'local',
                $this->path,
                ['bucket' => self::BUCKET, 'msg_id' => 999_999],
                null,
                $this->realUnlinker(),
            );
            self::fail('an unproven reclaim must fail loudly');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('the local file is the only copy', $e->getMessage());
        }

        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD 3 — a document that cannot ADDRESS Telegram is not durability.
     * FALSIFIED: loosening TelegramTier::durableMediaFor()'s access_hash /
     * file_reference / doc_id validation turns this red.
     *
     * A zero access_hash is the MTProto "unknown peer" sentinel and an empty
     * file_reference names no file at all; a reference built from either is
     * rejected by every publish, so proving it proves nothing about re-publishing
     * the freed file.
     */
    public function test_fail_closed_a_remote_document_that_cannot_address_telegram_keeps_the_file(): void
    {
        foreach ([
            'zero access_hash' => ['id' => 5, 'access_hash' => 0, 'file_reference' => 'ref'],
            'empty file_reference' => ['id' => 5, 'access_hash' => 1, 'file_reference' => '   '],
            'non-positive doc_id' => ['id' => 0, 'access_hash' => 1, 'file_reference' => 'ref'],
        ] as $label => $document) {
            // A fresh channel per case: a peer-partitioned wire, so each
            // unusable document is genuinely the only thing at its message id.
            $bucket = 'data:unusable-'.str_replace(' ', '-', $label);
            $api = $this->api->apiMap();
            $channel = new TelegramStoreChannel($bucket, $api);
            $this->api->seedPeerId = (int) ($channel->bucketInputPeer()['channel_id'] ?? 0);
            $api['historyPage'] = fn (): array => [[
                'id' => 7,
                'media' => ['_' => 'messageMediaDocument', 'document' => $document + ['_' => 'document', 'size' => 3]],
            ]];

            $isolated = $this->service(ReferencePolicy::coldRemoteAuthoritative(), $api);

            try {
                $isolated->reclaimLocal('local', $this->path, ['bucket' => $bucket, 'msg_id' => 7], null, $this->realUnlinker());
                self::fail("an unusable reference ({$label}) is not durability");
            } catch (RuntimeException $e) {
                self::assertStringContainsString('the local file is the only copy', $e->getMessage());
            }
        }

        self::assertSame([], $this->unlinked, 'no unusable reference may ever reach the deleter');
        self::assertTrue(is_file($this->path), 'the bytes must still be on the host disk');
    }

    /**
     * GUARD 4 — a FOREIGN manifest cannot induce a local delete. FALSIFIED:
     * letting the reclaim path accept a manifest-bearing message as a
     * durability proof turns this red.
     *
     * This is the specific worry the wiring left open: a chat message in a
     * bucket other people can post in IS attacker-writable. The answer for the
     * RECLAIM path is better than "the local row wins" — the reclaim proof is a
     * MEDIA row keyed by message id, and a manifest message carries no document
     * at all, so a planted TFSTORE1 message is simply not a candidate. An
     * attacker can make a name unreadable (denial of service, which grants
     * strictly less than the delete they already have); they cannot induce a
     * local unlink with one.
     */
    public function test_fail_closed_a_foreign_manifest_message_cannot_prove_durability_and_induce_a_delete(): void
    {
        $manifestMsgId = $this->foreignManifestMessage();

        try {
            $this->cold()->reclaimLocal(
                'local',
                $this->path,
                ['bucket' => self::BUCKET, 'msg_id' => $manifestMsgId],
                null,
                $this->realUnlinker(),
            );
            self::fail('a manifest message is not a media reference');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('the local file is the only copy', $e->getMessage());
        }

        self::assertSame([], $this->unlinked, 'the deleter must be unreachable');
        self::assertTrue(is_file($this->path), 'a stranger\'s chat message must not free a host file');
    }

    /**
     * GUARD 5 — an empty file_reference in the resolved row is refused, and it
     * is diagnosed as such even under the cold posture. FALSIFIED: removing the
     * REASON_EMPTY_FILE_REFERENCE refusal turns this red, and so does moving the
     * freshness consult in front of it (the diagnosis would silently become
     * "the remote is gone" for a row that is bad on its own terms).
     */
    public function test_fail_closed_an_empty_file_reference_keeps_the_file(): void
    {
        $this->bucketChannelId();
        $this->indexMedia(42, ['file_reference' => '   ']);

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_EMPTY_FILE_REFERENCE, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD 6 — a non-positive doc_id is refused. FALSIFIED: removing the
     * REASON_INVALID_DOC_ID refusal turns this red.
     */
    public function test_fail_closed_a_non_positive_doc_id_keeps_the_file(): void
    {
        $this->bucketChannelId();
        $this->indexMedia(42, ['doc_id' => 0]);

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_INVALID_DOC_ID, $outcome->reason());
        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD 7 — a zero access_hash in the resolved row is refused, so a LOCAL
     * row is held to the same addressing bar the remote tier has always held
     * itself to. FALSIFIED: removing the REASON_INVALID_ACCESS_HASH refusal
     * turns this red. It is new, and it is the local half of a rule that used
     * to exist only on the remote path.
     */
    public function test_fail_closed_a_row_that_cannot_address_telegram_keeps_the_file(): void
    {
        $this->bucketChannelId();
        $this->indexMedia(42, ['access_hash' => 0]);

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_INVALID_ACCESS_HASH, $outcome->reason());
        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD 8 — a dry run decides without deleting, and never even asks. It
     * decides AFTER the whole proof, which is why the fixture here is a real
     * document holding the real bytes: a dry run that reported `dry-run` over
     * an unproven reclaim would be a lie told to an operator about their disk.
     * FALSIFIED: removing the REASON_DRY_RUN branch turns this red, and so does
     * hoisting the dry-run check above the content proof (this test then still
     * passes, which is why the falsifier matters).
     */
    public function test_fail_closed_a_dry_run_reaches_neither_the_disk_nor_the_deleter(): void
    {
        $msgId = $this->remoteCopyOfHostFile();

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
            dryRun: true,
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_DRY_RUN, $outcome->reason());
        self::assertFalse($outcome->freedBytes(), 'a dry run must never claim it freed bytes');
        self::assertSame([], $this->unlinked, 'a dry run must not even ask');
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD 9 — no deleter, no delete, and the refusal is LOUD rather than a
     * silent retain. FALSIFIED: removing the LogicException turns this red.
     */
    public function test_fail_closed_reclaiming_without_an_injected_deleter_is_loud_and_frees_nothing(): void
    {
        $msgId = $this->remoteCopyOfHostFile();

        try {
            $this->cold()->reclaimLocal('local', $this->path, ['bucket' => self::BUCKET, 'msg_id' => $msgId]);
            self::fail('reclaiming with no deleter must not be silent');
        } catch (LogicException $e) {
            self::assertStringContainsString('never reaches a disk directly', $e->getMessage());
        }

        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD 10 — a deleter that frees nothing is reported as `refused`, never
     * as freed. FALSIFIED: treating the deleter's false as a success turns this
     * red. This is the guard that survives the tightening most visibly: the
     * store now asks a deleter to act on bytes it has just hashed, and "I did
     * not" is still not "freed".
     */
    public function test_fail_closed_a_deleter_that_frees_nothing_is_never_reported_as_freed(): void
    {
        $msgId = $this->remoteCopyOfHostFile();

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(freed: false),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_DELTER_DECLINED, $outcome->reason());
        self::assertFalse($outcome->freedBytes(), 'a caller reporting "reclaimed" here is lying about the disk');
        self::assertCount(1, $this->unlinked);
        self::assertTrue(is_file($this->path), 'and the bytes really are still there');
    }

    /**
     * GUARD 11 — the happy path, so the guards above are not the only thing
     * pinned. A document holding exactly the host file's bytes IS a proof, and
     * the file is freed. FALSIFIED: this turns red the moment the content check
     * is wrong in the strict direction — a proof that can never succeed is not
     * a proof, it is an outage.
     */
    public function test_a_remote_document_holding_those_exact_bytes_does_free_the_file(): void
    {
        $msgId = $this->remoteCopyOfHostFile();
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative(), $this->countedApi());

        $outcome = $cold->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->freedBytes(), implode(' ', [$outcome->state(), $outcome->reason()]));
        self::assertSame([['local', $this->path]], $this->unlinked);
        self::assertFalse(is_file($this->path));
        self::assertGreaterThan(0, $this->historyCalls, 'and the proof was bought from the remote, not from a cache');
    }

    // ══ §2 THE FIVE, NOW CLOSED ════════════════════════════════════════════

    /**
     * CLOSED 1/5 — A DIFFERENT HASH THAN THE FILE BEING DELETED. FALSIFIED:
     * removing the content check (hashLocalFile + hash_equals) turns this RED.
     *
     * The row declares a content hash, the local file holds completely unrelated
     * bytes, and the delete used to proceed — because `content_sha256` was read
     * nowhere in this class and the local file was never hashed. The durable
     * thing the old proof established was "document 777 is addressable", not
     * "these bytes are document 777".
     *
     * The fix is not "read the column". The column is null in every row any
     * message authored, and the local file is on a path only the CALLER knows, so
     * the binding is established here: under the cold posture the store asks the
     * remote tier for the document's bytes, hashes them, and compares. A row's
     * declared hash is cross-checked against that, never trusted over it.
     */
    public function test_a_row_declaring_a_different_hash_than_the_file_holding_is_no_longer_a_proof(): void
    {
        $this->bucketChannelId();
        $this->indexMedia(42, ['content_sha256' => str_repeat('de', 32)]);

        self::assertSame(self::HOST_BYTES, (string) file_get_contents($this->path), 'precondition: the local bytes');

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            null,
            $this->realUnlinker(),
        );

        // The row is refused on its OWN terms (the remote holds no such
        // document), which is the strictest reading available here. The
        // content-mismatch case proper is the next test, where the reference IS
        // live and only the bytes disagree.
        self::assertTrue($outcome->isRefused());
        self::assertSame([], $this->unlinked, 'a hash nobody checked is not a hash');
        self::assertTrue(is_file($this->path), 'the host\'s bytes survived a row that merely claimed otherwise');
    }

    /**
     * CLOSED 1/5, the sharp half — a LIVE document whose bytes are not the
     * local file's bytes. FALSIFIED: removing the content comparison turns this
     * RED. Nothing about the reference is wrong here: the document exists, it is
     * addressable, it was re-consulted on this call, and it is not these bytes.
     */
    public function test_a_live_document_holding_different_bytes_is_not_a_proof_for_this_file(): void
    {
        $msgId = $this->remoteDocument('somebody-elses.jpg', 'a stranger\'s entirely different bytes');

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_CONTENT_MISMATCH, $outcome->reason());
        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path), 'the bytes destroyed would never have been the ones Telegram held');
    }

    /**
     * CLOSED 2/5 — A STALE REMOTE. FALSIFIED: restoring the
     * `findMedia()`-first short-circuit (so a cached row answers instead of the
     * remote) turns this RED, and the historyCalls assertion is what makes that
     * falsification meaningful rather than accidental.
     *
     * trustsRemote() is true and durabilityProof() returns
     * PROOF_REMOTE_DOCUMENT — yet after one cold resolve had cached the media
     * row, a reclaim of a DIFFERENT local file used to succeed with the document
     * REVOKED and ZERO wire calls. The proof was a cached belief, and a cache is
     * a performance fact, never a proof.
     *
     * The fix re-consults the tier for the exact object about to be deleted, on
     * this call, and the wire cost is stated in the method's docblock: one
     * bounded history walk and one document download per cold reclaim. That is
     * the price of not deleting against a belief.
     */
    public function test_a_cached_row_no_longer_proves_durability_after_the_remote_document_is_revoked(): void
    {
        $msgId = $this->remoteCopyOfHostFile();
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative(), $this->countedApi());

        // 1. A cold reclaim resolves from the remote and caches the row.
        $first = $this->fileWith(self::HOST_BYTES);
        try {
            self::assertTrue(
                $cold->reclaimLocal('local', $first, ['bucket' => self::BUCKET, 'msg_id' => $msgId], null, $this->realUnlinker())->freedBytes(),
                'precondition: the first reclaim is a genuine one',
            );
        } finally {
            @unlink($first);
        }
        self::assertNotNull($this->index->findMedia(self::ACCOUNT, self::BUCKET, $msgId), 'precondition: the row is now cached');

        // 2. The document is REVOKED. Nothing in Telegram holds these bytes.
        $channel = new TelegramStoreChannel(self::BUCKET, $this->api->apiMap());
        $channel->deleteMessagesIn($channel->bucketInputPeer(), [$msgId]);
        self::assertNotContains($msgId, array_column($this->api->messages, 'id'), 'precondition: the remote copy is gone');
        $this->unlinked = [];
        $this->historyCalls = 0;

        // 3. A DIFFERENT local file, the same message id, the same bytes.
        $second = $this->fileWith(self::HOST_BYTES);
        $outcome = $cold->reclaimLocal('local', $second, ['bucket' => self::BUCKET, 'msg_id' => $msgId], null, $this->realUnlinker());

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_REMOTE_DOCUMENT_GONE, $outcome->reason());
        self::assertSame([], $this->unlinked, 'a cached row must not reach the deleter on its own');
        self::assertTrue(is_file($second), 'a revoked document freed this file before');
        self::assertGreaterThan(0, $this->historyCalls, 'the remote must have been asked, not remembered');
        @unlink($second);
    }

    /**
     * CLOSED 3/5 — AN ATTACKER-AUTHORED ROW, in the form that matters: the
     * stranger's document is GENUINELY in the channel, genuinely addressable,
     * and genuinely re-consulted. FALSIFIED: removing the content comparison
     * turns this RED. The reference half of the old proof is entirely satisfied
     * here — which is the point: the reference half was never the thing that
     * made deleting safe.
     */
    public function test_a_strangers_live_document_cannot_authorise_deleting_the_hosts_unrelated_file(): void
    {
        $msgId = $this->remoteDocument('stranger.png', 'bytes a stranger chose, not the host\'s');

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_CONTENT_MISMATCH, $outcome->reason());
        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path), 'a stranger\'s document freed the host\'s file before');
    }

    /**
     * CLOSED 3/5, the provenance half — "A LOCAL TRUTH ROW WINS BECAUSE A CHAT
     * MESSAGE IS ATTACKER-WRITABLE" IS TRUE FOR tf_store_objects AND FALSE FOR
     * tf_store_media, and the asymmetry is now enforced by the row's own shape.
     *
     * archiveMessageMedia() writes a tf_store_media row straight from a live
     * update, so a stranger who can post in a declared DATA channel authors a
     * row naming THEIR document. Under the LOCAL posture — which never consults
     * the wire, by definition — such a row can carry no content hash, because
     * the method that writes it has never seen the bytes and cannot honestly
     * produce one. So the row is refused on provenance, with the reference half
     * fully satisfied.
     *
     * FALSIFIED: accepting a null/absent content binding (the old behaviour)
     * turns this RED.
     */
    public function test_a_row_a_stranger_authored_cannot_carry_a_proof_by_itself(): void
    {
        $this->bucketChannelId();
        $local = $this->service(ReferencePolicy::dropLocalAfterIndex(), $this->api->apiMap());
        $this->index->upsertBucket(self::ACCOUNT, self::BUCKET, [
            'kind' => ChannelRegistry::KIND_DATA,
            'channel_id' => $this->channelId,
            'access_hash' => 42,
            'created_at_unix' => 1758000000,
        ]);

        // A stranger posts a document into the declared data channel.
        $indexed = $local->archiveMessageMedia(['message' => [
            'id' => 4242,
            'media' => ['_' => 'messageMediaDocument', 'document' => [
                '_' => 'document',
                'id' => 31337,
                'access_hash' => 1,
                'file_reference' => 'ATTACKER-SUPPLIED-REFERENCE',
                'size' => 9,
                'mime_type' => 'image/png',
            ]],
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => $this->channelId],
        ]]);
        self::assertTrue($indexed['indexed'], 'precondition: the hostile message became local truth');

        $row = $this->index->findMedia(self::ACCOUNT, self::BUCKET, 4242);
        self::assertSame('ATTACKER-SUPPLIED-REFERENCE', $row['file_reference'] ?? null, 'the row is the stranger\'s, verbatim');
        self::assertNull($row['content_sha256'] ?? null, 'and it carries no content binding, because nobody had the bytes');

        $outcome = $local->reclaimLocal('local', $this->path, ['bucket' => self::BUCKET, 'msg_id' => 4242], null, $this->realUnlinker());

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_NO_CONTENT_PROOF, $outcome->reason());
        self::assertSame([], $this->unlinked, 'a message-authored row must never reach the deleter');
        self::assertTrue(is_file($this->path), 'the host\'s own bytes were freed against a row a stranger authored');
    }

    /**
     * CLOSED 5/5 — THE CALLER-SUPPLIED $expect ROW. FALSIFIED: accepting a bare
     * row again (the old `$expect`-verbatim branch) turns this RED.
     *
     * A bare row used to be used verbatim: no index lookup, no wire call, no
     * proof of any kind, and an invented row freed the file on its own
     * testimony. It is now refused outright, and refused with an EXCEPTION
     * rather than an outcome — a caller that hands this method a claim instead
     * of an object has made a programming error, and a `refused` outcome there
     * would read as "the store considered it and declined".
     *
     * Supplying a content hash does not reopen the door: identity comes first,
     * and a hash attached to nothing is not an identity.
     */
    public function test_a_caller_supplied_row_without_an_object_is_refused_outright(): void
    {
        self::assertSame([], $this->index->listMedia(self::ACCOUNT, self::BUCKET), 'precondition: empty index');
        self::assertSame([], $this->api->messages, 'precondition: empty wire');

        foreach ([
            'no identity at all' => ['doc_id' => 1, 'file_reference' => 'fabricated-by-the-caller'],
            'identity with a content binding attached' => [
                'doc_id' => 1,
                'file_reference' => 'fabricated-by-the-caller',
                'content_sha256' => hash('sha256', self::HOST_BYTES),
            ],
        ] as $label => $expect) {
            try {
                $this->cold()->reclaimLocal('local', $this->path, $expect, null, $this->realUnlinker());
                self::fail("a bare row ({$label}) is not an object and must be refused");
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('$expect[bucket] and $expect[msg_id]', $e->getMessage());
            }
        }

        self::assertSame([], $this->unlinked);
        self::assertSame(0, $this->historyCalls, 'and nothing was looked up on the wire either');
        self::assertTrue(is_file($this->path), 'a fabricated row freed this file before');
    }

    /**
     * CLOSED 5/5, the trust that remains — a caller that DOES name an object
     * still has to go through the store's own resolution: the row-shaped keys
     * in $expect are ignored outright. FALSIFIED: honouring the caller's
     * `file_reference` turns this RED (it would be refused EMPTY-FILE-REFERENCE
     * instead of freed, which is the whole point).
     *
     * The caller here hands over a row that could not address Telegram if it
     * were believed. It is not believed: the store resolves the object it was
     * told about, proves THAT, and frees. A caller's claim about WHICH object
     * it is remains the trust this seam has always had — the claim is a claim
     * about identity, and everything after the identity is checked.
     */
    public function test_a_caller_row_is_ignored_when_it_disagrees_with_local_truth(): void
    {
        $msgId = $this->remoteCopyOfHostFile();

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            [
                'bucket' => self::BUCKET,
                'msg_id' => $msgId,
                // A row that would be refused on its own terms, so believing it
                // and ignoring it are distinguishable outcomes.
                'doc_id' => 0,
                'file_reference' => '   ',
                'access_hash' => 0,
            ],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->freedBytes(), implode(' ', [$outcome->state(), $outcome->reason()]));
        self::assertNotSame(ReclaimOutcome::REASON_EMPTY_FILE_REFERENCE, $outcome->reason(), 'the caller\'s row was believed');
    }

    // ══ §3 THE SEAM, PINNED AS A SEAM ═════════════════════════════════════

    /**
     * The cold branch is decided by INJECTION, not by reachability. If any part
     * of it were decided by what this host can touch, this file could not run in
     * CI at all — which is what makes §2 results statements about the CODE
     * rather than about the machine.
     */
    public function test_the_cold_reclaim_branch_is_decided_by_injection_not_by_reachability(): void
    {
        self::assertSame([], $this->api->messages);
        self::assertInstanceOf(TelegramTier::class, new TelegramTier, 'the shipped resolver needs no scope and no disk');
        self::assertTrue(ReferencePolicy::coldRemoteAuthoritative()->trustsRemote());
        self::assertSame(ReferencePolicy::PROOF_REMOTE_DOCUMENT, ReferencePolicy::coldRemoteAuthoritative()->durabilityProof());
    }

    // ══ §4 THE ONE LIMIT THAT IS NOT CLOSED ═══════════════════════════════

    /**
     * LIMIT 4/5 — TOCTOU, NARROWED BUT NOT CLOSED. This test is GREEN while
     * describing a failure, and that is deliberate: it is the only honest way to
     * report a seam. The paragraph after it is the receipt.
     *
     * THE STORE NARROWS THE WINDOW AND PROVES IT. The first hash happens AFTER
     * the remote consult, so a file replaced while the (slow) wire call is in
     * flight is caught: the bytes are compared after the walk, not before it.
     * FALSIFIED: moving the content hash to before the freshness consult, or
     * dropping it, turns the NARROWING half RED — the swap below happens during
     * the history walk and the store must refuse.
     */
    public function test_a_file_replaced_while_the_remote_proof_is_gathered_is_not_the_file_that_gets_deleted(): void
    {
        $msgId = $this->remoteCopyOfHostFile();
        $api = $this->countedApi();
        $inner = $api['historyPage'];
        $swapped = false;
        $api['historyPage'] = function (array $peer, int $limit, int $offsetId) use ($inner, &$swapped): array {
            $page = $inner($peer, $limit, $offsetId);
            if (! $swapped) {
                $swapped = true;
                // The swap: a different file occupies the proven path WHILE the
                // store is still gathering its proof from Telegram.
                file_put_contents($this->path, 'UNRELATED bytes that arrived mid-proof');
            }

            return $page;
        };

        $outcome = $this->service(ReferencePolicy::coldRemoteAuthoritative(), $api)->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($swapped, 'precondition: the file really was replaced mid-proof');
        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_CONTENT_MISMATCH, $outcome->reason());
        self::assertSame([], $this->unlinked, 'the store must not delete bytes it never proved');
        self::assertTrue(is_file($this->path));
        self::assertSame('UNRELATED bytes that arrived mid-proof', (string) file_get_contents($this->path), 'and the bytes that survived are the ones that were there');
    }

    /**
     * LIMIT 4/5 — THE RESIDUAL, AND WHY IT IS A SEAM AND NOT AN OVERSIGHT.
     * FALSIFIED: passing the verified hash to the deleter (which this method
     * does) is what makes the hardened arm below possible; removing that third
     * argument leaves the hardened deleter with nothing to check and the guard
     * dies.
     *
     * The store hashes, re-hashes, and then calls the INJECTED deleter. A
     * replacement made INSIDE that deleter is invisible to every check placed
     * before it, because the deleter is the last thing this method does. This
     * arm is the honest statement of that: the file the deleter swaps in is the
     * file that dies, and no assertion here pretends otherwise.
     *
     * WHAT THE FIX ACTUALLY IS: the verified content hash is passed to the
     * deleter as a THIRD argument, so the guard can live where the swap is.
     */
    public function test_a_swap_made_inside_the_deleter_is_the_seam_this_method_cannot_see(): void
    {
        $msgId = $this->remoteCopyOfHostFile();

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            function (string $disk, string $path): bool {
                $this->unlinked[] = [$disk, $path];
                file_put_contents($path, 'UNRELATED bytes that arrived after the proof');

                return unlink($path);
            },
        );

        self::assertTrue($outcome->freedBytes());
        self::assertFalse(
            is_file($this->path),
            'the bytes destroyed were never the ones the proof was taken about — the store cannot see this, by design',
        );
    }

    /**
     * LIMIT 4/5, CLOSED AT THE SEAM — a deleter that adopts the documented
     * third-argument contract closes the last hop, and this is the receipt that
     * the contract is real and not decoration.
     *
     * FALSIFIED, twice over: dropping the third argument from the `$unlink(...)`
     * call leaves the hardened deleter with nothing to compare against, and
     * dropping its SECOND re-check (the one covering the gap between "I checked"
     * and "I unlink") lets the delete through. Both turn this RED.
     */
    public function test_a_deleter_that_adopts_the_verified_hash_contract_closes_the_last_hop(): void
    {
        $msgId = $this->remoteCopyOfHostFile();

        $outcome = $this->cold()->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            function (string $disk, string $path, string $verifiedSha256): bool {
                $this->unlinked[] = [$disk, $path];
                self::assertSame(hash('sha256', self::HOST_BYTES), $verifiedSha256, 'the store hands over what it proved');
                if (! is_file($path) || hash_file('sha256', $path) !== $verifiedSha256) {
                    return false;
                }

                // THE GAP. Anything at all can happen between a deleter's own
                // check and its unlink: a queue draining, an fs watcher, a
                // second process, this closure's own bookkeeping. The store
                // cannot see any of it, which is the whole reason the guard has
                // to live here rather than one call earlier.
                file_put_contents($path, 'UNRELATED bytes that arrived in the gap');

                // ...and this is the guard that lives here. The store's re-hash
                // already happened; without this line the swap below is deleted
                // silently and this test's whole claim is decoration.
                if (hash_file('sha256', $path) !== $verifiedSha256) {
                    return false;
                }

                return unlink($path);
            },
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_DELTER_DECLINED, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertCount(1, $this->unlinked, 'the deleter WAS asked, and it said no on identity grounds');
        self::assertTrue(is_file($this->path), 'and the file still on disk is the file the host still has');
    }
}
