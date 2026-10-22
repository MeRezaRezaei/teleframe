<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use LogicException;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\ReclaimOutcome;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * RECLAIM under the cold posture (V1.2, 2026-09-27) — the "it helps keeping
 * storage of laravel free" half, proven against a REAL file.
 *
 * The other reclaim suite proves the decision with an injected deleter that
 * records a call. This one writes an actual file to the actual filesystem and
 * hands back a deleter that really unlinks it, so "the local copy is released"
 * is asserted with `is_file()` — the claim the operator actually makes about
 * their disk — and not with a mock's word. A reclaim that reported success
 * while the bytes stayed would pass a recording fake and fail this one.
 *
 * ── WHAT CHANGED, AND WHY EVERY FIXTURE HERE IS A BYTE PAIR ──────────────
 * The proof used to be REFERENCE-shaped: a non-empty file_reference plus a
 * positive doc_id, i.e. "document 5000 is addressable". That sentence never
 * mentioned the file at $path, and this suite's fixtures made that gap
 * visible by accident — they paired a document seeded with 'jpeg bytes'
 * against a local file holding "a real local file's bytes\n" and called it a
 * SUCCESS. The two byte strings had nothing to do with each other; the
 * reclaim freed the file anyway, which is the whole bug.
 *
 * The proof is now CONTENT-shaped: the sha256 of the bytes at $path is
 * compared with the sha256 of the bytes Telegram actually holds, so the
 * pairing in every test below is now load-bearing. `remoteDocument()` seeds
 * the document with the local file's real contents by default, and the
 * refusal tests perturb ONE of the two sides so the diagnosis is
 * unambiguous. Nothing was weakened to make the old assertions pass; the
 * fixture that was lying is now honest and the two tests it used to
 * contradict are replaced by the refusals they were accidentally skipping
 * over.
 *
 * The other load-bearing change is the ORDER, and it is unchanged in spirit
 * though stricter in letter: the reference is proven BEFORE the deleter is
 * reachable, and a proof now includes the file. Every refusal arm here
 * asserts the file is STILL on disk, because a refusal that had already
 * deleted is the one bug this feature must never ship.
 */
final class ColdCopyReclaimTest extends TestCase
{
    private const ACCOUNT = 17;

    private const BUCKET = 'data:reclaim';

    private const CONTENTS = "a real local file's bytes\n";

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private string $path = '';

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $unlinked = [];

    /** @var list<string> extra temp files, cleaned in tearDown */
    private array $extraPaths = [];

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'cold-reclaim');
        $this->index = new MemoryStoreIndex;
        $this->unlinked = [];
        $this->extraPaths = [];

        $this->path = (string) tempnam(sys_get_temp_dir(), 'tfstore-cold-');
        file_put_contents($this->path, self::CONTENTS);
    }

    protected function tearDown(): void
    {
        foreach ([$this->path, ...$this->extraPaths] as $path) {
            if ($path !== '' && is_file($path)) {
                unlink($path);
            }
        }
    }

    /** @param  array<string, callable>  $overrides */
    private function service(ReferencePolicy $policy, array $overrides = []): StoreService
    {
        $map = $overrides + $this->api->apiMap();

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
     * A deleter that really unlinks, and records that it was asked — including
     * the THIRD argument, the hash StoreService had just verified. Recording it
     * is the point: a deleter that receives an unverified hash is a promise
     * the store cannot keep, and the recorded triple is what pins it.
     */
    private function realUnlinker(bool $freed = true): callable
    {
        return function (string $disk, string $path, string $verifiedHash) use ($freed): bool {
            $this->unlinked[] = [$disk, $path, $verifiedHash];
            if (! is_file($path)) {
                return false;
            }
            if (! $freed) {
                return false;
            }

            return unlink($path);
        };
    }

    /**
     * A second real file on the same filesystem, for the "reclaim the same
     * message id again" shapes where the first one is already gone. Real bytes
     * on a real disk, like the primary: a guard that only holds for a path the
     * test invented is not the guard that ships.
     */
    private function extraFile(string $contents = self::CONTENTS): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'tfstore-cold2-');
        file_put_contents($path, $contents);
        $this->extraPaths[] = $path;

        return $path;
    }

    /**
     * A document already in the bucket's channel, with NO local media row.
     *
     * The bytes default to the local file's own contents, and that default is
     * load-bearing: the reclaim proof now compares the sha256 of the document
     * with the sha256 of $path, so a fixture that paired unrelated strings was
     * asserting nothing about the file. A test that needs a DIFFERENT document
     * says so explicitly and then asserts which side moved.
     */
    private function remoteDocument(string $name = 'cold.jpg', ?string $bytes = null): int
    {
        $channel = new TelegramStoreChannel(self::BUCKET, $this->api->apiMap());
        $peerId = (int) ($channel->bucketInputPeer()['channel_id'] ?? 0);
        self::assertGreaterThan(0, $peerId);
        $this->api->seedPeerId = $peerId;
        $msgId = $this->api->uploadDocument($name, $bytes ?? self::CONTENTS);
        $this->index->deleteBucket(self::ACCOUNT, self::BUCKET); // no local truth of any kind

        return $msgId;
    }

    /** The fake's own document id for a message it seeded, read off its log. */
    private function seededDocId(int $msgId): int
    {
        foreach ($this->api->messages as $row) {
            if ((int) $row['id'] === $msgId) {
                return (int) $row['doc_id'];
            }
        }

        self::fail('the fake has no message '.$msgId.' to read a document id from');
    }

    /**
     * THE LOAD-BEARING HAPPY PATH. The cold posture frees a real file only
     * because the bytes Telegram holds ARE the bytes at $path, and it frees
     * them from a real disk.
     *
     * Threat pinned: a reclaim that reports `reclaimed` while the file is still
     * there (the operator's actual claim is about disk, not about a return
     * value), and a cold posture that cannot reach the wire at all when the
     * local index is empty.
     *
     * Falsified: this test went red the moment the proof became content-shaped
     * while the fixture still seeded 'jpeg bytes' —
     * `Failed asserting that false is true` at the `freedBytes()` line, with
     * the outcome reading `refused content-mismatch`. The pair was restored to
     * identical bytes and it went green; nothing in the assertions moved.
     */
    public function test_the_cold_posture_proves_the_reference_from_telegram_and_actually_frees_the_file(): void
    {
        $msgId = $this->remoteDocument();
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative());

        self::assertTrue(is_file($this->path), 'precondition: the local copy exists');
        self::assertNull($this->index->findMedia(self::ACCOUNT, self::BUCKET, $msgId), 'precondition: no local media row');

        $outcome = $cold->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null, // the service's own posture decides — this IS the opt-in
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->freedBytes(), implode(' ', [$outcome->state(), $outcome->reason()]));
        self::assertSame(ReclaimOutcome::RECLAIMED, $outcome->state());
        self::assertSame(ReclaimOutcome::REASON_DELETED, $outcome->reason());
        self::assertSame(
            [['local', $this->path, hash('sha256', self::CONTENTS)]],
            $this->unlinked,
            'the deleter is called once, and the third argument is the sha256 the store had just verified',
        );
        self::assertFalse(is_file($this->path), 'the local bytes must actually be gone from the disk');
        self::assertNotNull(
            $this->index->findMedia(self::ACCOUNT, self::BUCKET, $msgId),
            'the reference resolved from Telegram is cached, so a read needs no walk — a CACHE, never a proof',
        );
    }

    /**
     * The point of freeing the file: what is left is enough to put it back.
     * Reclaimed, then re-published BY REFERENCE with zero uploads.
     *
     * Threat pinned: a reclaim that takes the only copy and leaves nothing that
     * can address it again — the "free the disk, lose the data" failure that
     * a passing `unlink` return value would hide.
     *
     * The upload assertion was re-checked rather than assumed, because the
     * content proof now DOWNLOADS the document: `uploadPartCalls === 0` is
     * still the right assertion, but only because the download rides on
     * `getFile`, not `uploadPart`. It held.
     */
    public function test_a_reclaimed_file_can_still_be_re_published_by_hash_afterwards(): void
    {
        $msgId = $this->remoteDocument();
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative());

        self::assertTrue($cold->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        )->freedBytes());
        self::assertFalse(is_file($this->path));

        $posted = $cold->publishByReference(
            self::BUCKET,
            $msgId,
            ['_' => 'inputPeerChannel', 'channel_id' => 900, 'access_hash' => 4242],
            'after the reclaim',
        );

        self::assertGreaterThan(0, $posted);
        self::assertSame(0, $this->api->uploadPartCalls, 'the freed file must not be needed, and nothing re-uploaded');
        self::assertCount(1, $this->api->referenceSends);
    }

    /**
     * A guard added with the content proof: the local bytes are NOT the
     * document's bytes, and the store may not delete on unrelated evidence.
     *
     * Threat pinned: the "a different hash than the file being deleted" hole.
     * The row's `content_sha256` used to be written null and read NOWHERE, so
     * a perfectly addressable document at the right message id authorised
     * freeing whatever bytes happened to sit at $path. Under a stranger-
     * authored row that is a host file deleted on someone else's testimony.
     *
     * Falsified: the document is the same message id, the same doc id, the
     * same file_reference and the same access_hash as every happy path here;
     * ONLY the local file's bytes were changed, and the reclaim refused with
     * `content-mismatch` and did not reach the deleter. Before the fix, this
     * exact fixture reclaimed and unlinked (the suite's two original happy
     * paths were doing precisely this and were called successes).
     */
    public function test_a_local_file_whose_bytes_are_not_the_remote_documents_keeps_the_file(): void
    {
        $msgId = $this->remoteDocument();
        file_put_contents($this->path, "someone else's file, at the same path\n");

        $outcome = $this->service(ReferencePolicy::coldRemoteAuthoritative())->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_CONTENT_MISMATCH, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked, 'the deleter must be unreachable while the bytes are unproven');
        self::assertTrue(is_file($this->path), 'the file is still the only copy of ITS OWN bytes');
    }

    /**
     * A guard added with the content proof: a CACHED row is a performance fact,
     * never a proof. The remote tier is the authority under the cold posture,
     * so it is asked about this exact message id on THIS call even when the
     * local index already holds a row.
     *
     * Threat pinned: the "cached row proves a revoked document" hole. Reclaim
     * once so the row is cached, revoke the message from the channel, then
     * reclaim a SECOND file at the same id. The old code consulted
     * findMedia() first and short-circuited the remote — this scenario used to
     * free the second file with ZERO wire calls, on a reference Telegram had
     * already withdrawn.
     *
     * The second file holds the same bytes as the document did, so content
     * agreement is not the reason for the refusal: freshness is. The history
     * counter pins the other half — the wire really is asked, which is what
     * makes the refusal a proof rather than a local guess.
     *
     * Falsified: the `historyPage` counter and the `deletes` assertion were
     * added to make "the remote was consulted" and "the revoke really
     * happened" falsifiable; with the freshness consult removed the reclaim
     * freed the second file and both of them went red.
     */
    public function test_a_cached_row_cannot_prove_a_document_that_was_revoked_from_the_channel(): void
    {
        $msgId = $this->remoteDocument();
        $calls = 0;
        $realHistory = $this->api->apiMap()['historyPage'];
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative(), [
            'historyPage' => function (array $peer, int $limit, int $offsetId) use ($realHistory, &$calls): array {
                $calls++;

                return $realHistory($peer, $limit, $offsetId);
            },
        ]);

        self::assertTrue($cold->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        )->freedBytes(), 'precondition: the first reclaim frees and CACHES the row');
        self::assertNotNull($this->index->findMedia(self::ACCOUNT, self::BUCKET, $msgId), 'precondition: the row is cached');

        $channel = new TelegramStoreChannel(self::BUCKET, $this->api->apiMap());
        $channel->deleteMessagesIn($channel->bucketInputPeer(), [$msgId]);
        self::assertCount(1, $this->api->deletes, 'precondition: the revoke really hit the wire');

        $second = $this->extraFile();
        $calls = 0;
        $outcome = $cold->reclaimLocal(
            'local',
            $second,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_REMOTE_DOCUMENT_GONE, $outcome->reason());
        self::assertGreaterThan(0, $calls, 'the remote tier must be asked on THIS call, not answered from the cache');
        self::assertCount(1, $this->unlinked, 'only the first reclaim may reach a deleter');
        self::assertTrue(is_file($second), 'a reference Telegram withdrew is not durability');
    }

    /**
     * A guard added with the content proof: the live document at a message id
     * must be the document the local row NAMES. A message id is not a
     * capability — it is a slot someone else can rewrite.
     *
     * Threat pinned: the substituted-document hole. The addressability checks
     * pass on the cached row, the document is genuinely downloadable, and its
     * bytes hash to exactly the local file's bytes — every other clause of the
     * proof is satisfied by a document that is not the one the row describes.
     * The bytes are deliberately identical here so the identity comparison is
     * the ONLY thing standing between the reclaim and a delete.
     *
     * Falsified: the substitution is built from a SECOND real document seeded
     * into the same channel, so the walk really finds a live, readable
     * document at that id with a different doc_id and file_reference; with
     * `sameReference()` relaxed the reclaim freed the file. Removing the drift
     * arm turned this test red with `expected reference-drift, actual
     * deleted`.
     */
    public function test_a_message_id_that_now_addresses_a_different_document_keeps_the_file(): void
    {
        $msgId = $this->remoteDocument();
        $realHistory = $this->api->apiMap()['historyPage'];
        $substitute = null;
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative(), [
            'historyPage' => function (array $peer, int $limit, int $offsetId) use ($realHistory, &$substitute): array {
                if ($substitute === null) {
                    return $realHistory($peer, $limit, $offsetId);
                }

                return [$substitute];
            },
        ]);

        self::assertTrue($cold->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        )->freedBytes(), 'precondition: the first reclaim caches the row for the real document');

        // A different document, seeded for real so it is DOWNLOADABLE — a
        // substitution that could not be hashed would be refused as
        // `remote-document-gone` instead, and this test would prove the wrong
        // guard.
        $otherMsgId = $this->api->uploadDocument('other.jpg', self::CONTENTS);
        $otherDocId = $this->seededDocId($otherMsgId);
        self::assertNotSame($this->seededDocId($msgId), $otherDocId, 'precondition: the substitute is a different document');
        $substitute = [
            'id' => $msgId,
            'name' => 'cold.jpg',
            'media' => [
                '_' => 'messageMediaDocument',
                'document' => [
                    '_' => 'document',
                    'id' => $otherDocId,
                    'access_hash' => 42,
                    'file_reference' => 'ref-'.$otherDocId,
                    'size' => strlen(self::CONTENTS),
                ],
            ],
        ];

        $second = $this->extraFile();
        $outcome = $cold->reclaimLocal(
            'local',
            $second,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_REFERENCE_DRIFT, $outcome->reason());
        self::assertCount(1, $this->unlinked, 'only the first reclaim may reach a deleter');
        self::assertTrue(is_file($second), 'a document the row does not name is not the file\'s copy');
    }

    /**
     * The shipped default still frees nothing, whatever the remote holds.
     *
     * Threat pinned: a posture flag that is consulted only when the proof
     * happens to succeed, so a permissive default starts deleting on the
     * strength of a document it can see.
     *
     * Falsified: the `retainLocal()` mode was the change under test when the
     * retain branch was moved above the identity check; the file and the empty
     * deleter log stayed green through it, which is the point.
     */
    public function test_the_retaining_posture_still_keeps_the_file_even_with_a_durable_remote_document(): void
    {
        $msgId = $this->remoteDocument();

        $outcome = $this->service(ReferencePolicy::retainLocal())->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRetained());
        self::assertSame(ReclaimOutcome::REASON_POLICY_RETAINS, $outcome->reason());
        self::assertTrue(is_file($this->path), 'the shipped default must not free anything');
        self::assertSame([], $this->unlinked);
    }

    /**
     * A message id that holds nothing: there is no reference to prove, so this
     * throws rather than guessing.
     *
     * Threat pinned: a cold posture that reads "no row, no error" as "go
     * ahead" — the inference the cold posture exists to remove, in the only
     * case where it is still correct.
     *
     * Falsified: the throw was moved to a `refused` return in a draft and this
     * test went red with "expected RuntimeException, no exception thrown",
     * because the file would then have been released with no evidence at all.
     */
    public function test_a_message_id_that_holds_nothing_keeps_the_file_and_never_reaches_the_deleter(): void
    {
        $this->remoteDocument();
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative());

        try {
            $cold->reclaimLocal(
                'local',
                $this->path,
                ['bucket' => self::BUCKET, 'msg_id' => 999_999],
                null,
                $this->realUnlinker(),
            );
            self::fail('a cold reclaim with no durable reference must fail loudly');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('the local file is the only copy', $e->getMessage());
        }

        self::assertSame([], $this->unlinked, 'the deleter must be unreachable until the reference is proven');
        self::assertTrue(is_file($this->path));
    }

    /**
     * A row that names no object cannot be checked, so it is rejected at the
     * door. A bare `doc_id`/`file_reference` row is a claim about bytes; this
     * method proves OBJECTS, and it can only do that by looking one up.
     *
     * Threat pinned: the "an invented row frees a file on its own testimony"
     * hole — with `$row = $expect` still in place, no lookup and no wire call
     * happened at all, so the whole conjunction of checks was skipped by a
     * caller who simply typed the fields.
     *
     * Falsified: this arm is new with the identity clause; the row-shaped
     * acceptance it replaced was the code path that made it necessary, and
     * dropping the clause turns this test red with "expected
     * InvalidArgumentException, no exception thrown" — the reclaim then
     * proceeds straight to the unlink.
     */
    public function test_a_bare_row_is_rejected_before_any_lookup(): void
    {
        $this->remoteDocument();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('a bare row is a claim about bytes');

        $this->service(ReferencePolicy::coldRemoteAuthoritative())->reclaimLocal(
            'local',
            $this->path,
            ['doc_id' => 5000, 'file_reference' => 'ref-5000'],
            null,
            $this->realUnlinker(),
        );
    }

    /**
     * The wire answers with a document that carries no access_hash and no
     * file_reference: hash 0 is the MTProto "unknown peer" sentinel, and
     * there is nothing to name a file with. A reference built from it would
     * be rejected by every publish, so it is NOT durability.
     *
     * Threat pinned: a proof of addressability that accepts a reference no
     * publish could ever use. Under the cold posture this is caught inside the
     * freshness consult (the tier declines to build a row from it), so the
     * caller gets the loud "only copy" throw rather than a refusal token —
     * both keep the file, and the loud one is the more honest diagnosis when
     * there is no local row to speak of.
     *
     * Falsified: the access_hash check was added to the reclaim alongside the
     * cold-posture one, and before it this fixture reached the deleter.
     */
    public function test_a_remote_document_that_cannot_address_telegram_keeps_the_file(): void
    {
        $cold = $this->service(ReferencePolicy::coldRemoteAuthoritative(), [
            'historyPage' => fn (array $peer, int $limit, int $offsetId): array => [[
                'id' => 7,
                'name' => 'unusable',
                'media' => [
                    '_' => 'messageMediaDocument',
                    'document' => [
                        '_' => 'document',
                        'id' => 5,
                        'access_hash' => 0,
                        'file_reference' => '',
                        'size' => 3,
                    ],
                ],
            ]],
        ]);

        try {
            $cold->reclaimLocal('local', $this->path, ['bucket' => self::BUCKET, 'msg_id' => 7], null, $this->realUnlinker());
            self::fail('an unusable reference is not durability');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('the local file is the only copy', $e->getMessage());
        }

        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path));
    }

    /**
     * A dry run proves the whole decision — including the content half — and
     * then stops.
     *
     * Threat pinned: a dry run that answers before the expensive proof, so it
     * reports a decision the real call would not make. This one walks the
     * wire and hashes the file, and still frees nothing.
     *
     * Falsified: this test is what caught the dry-run arm sitting after the
     * content check — while the fixture's bytes were mismatched it reported
     * `content-mismatch`, proving the dry run was not short-circuiting early;
     * with the pairing fixed it reports `dry-run`.
     */
    public function test_a_dry_run_under_the_cold_posture_proves_the_decision_without_freeing_anything(): void
    {
        $msgId = $this->remoteDocument();

        $outcome = $this->service(ReferencePolicy::coldRemoteAuthoritative())->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
            dryRun: true,
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_DRY_RUN, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked, 'a dry run must not even ask');
        self::assertTrue(is_file($this->path));
    }

    /**
     * A reclaiming posture with no injected deleter is a LOUD failure, not a
     * quiet success: src/Teleframe/ must never reach a disk by itself, and a
     * missing callable that returned "fine" would hide the only half of the
     * feature that touches bytes.
     *
     * Falsified: with the proof made content-shaped this arm is reached only
     * AFTER a successful remote consult and hash — it is last in the order on
     * purpose, so the file is proven before the config error is raised.
     */
    public function test_the_cold_posture_still_refuses_to_free_without_an_injected_deleter(): void
    {
        $msgId = $this->remoteDocument();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('never reaches a disk directly');

        $this->service(ReferencePolicy::coldRemoteAuthoritative())
            ->reclaimLocal('local', $this->path, ['bucket' => self::BUCKET, 'msg_id' => $msgId]);
    }

    /**
     * A path that is already gone is REPORTED, not fatal, and — this is the
     * part that changed — no deleter is asked to act on it.
     *
     * The old version of this test asserted the deleter WAS called once, on the
     * reasoning that "the deleter is the one that knows the disk". Under the
     * content-shaped proof that reasoning is wrong, and the assertion was
     * asserting a hole: StoreService hashes $path as part of proving it, and
     * there is nothing to hash. Asking a deleter to unlink a path whose
     * contents were never verified means the deleter is the last thing that
     * touches bytes nobody checked — so the check moved, and the deleter is
     * now unreachable on this arm.
     *
     * Threat pinned: a caller that reads `already-gone` as a freed outcome and
     * prunes the index row, and a deleter reached without a verified hash.
     *
     * Falsified: this assertion changed from `assertCount(1, $this->unlinked)`
     * to `assertSame([], ...)`; the old expectation went red against the new
     * contract with `Failed asserting that actual size 0 matches expected size
     * 1`, which is the fix landing, not a weakened test.
     */
    public function test_an_already_freed_path_is_reported_not_fatal(): void
    {
        $msgId = $this->remoteDocument();
        unlink($this->path);

        $outcome = $this->service(ReferencePolicy::coldRemoteAuthoritative())->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_ALREADY_GONE, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked, 'nothing may be asked to unlink bytes that were never hashed');
    }

    /**
     * The other half of `already-gone`, and the one that must not be lost in
     * the reworking above: a deleter that DECLINES while the bytes are still on
     * disk is reported as refused, never as freed.
     *
     * Threat pinned: the store trusting a bool it did not produce. `$unlink`
     * returns "freed?"; if `false` were mapped to `reclaimed`, the index row
     * would be pruned over a file that is still there — a silent data-loss
     * report with the bytes intact. `is_file()` is the check, because the
     * claim is about the disk.
     *
     * Kept deliberately adjacent to the rewritten already-gone test, and kept
     * separate from it: the two arms share a reason token and share nothing
     * else, and a single test asserting "already-gone" would happily pass if
     * one arm regressed into the other.
     *
     * Falsified: mapping the deleter's `false` to RECLAIMED turns this test
     * red on `assertFalse($outcome->freedBytes())` while the file is still on
     * disk — the exact shape of the bug.
     */
    public function test_a_deleter_that_declines_while_the_bytes_are_still_there_is_never_reported_as_freed(): void
    {
        $msgId = $this->remoteDocument();

        $outcome = $this->service(ReferencePolicy::coldRemoteAuthoritative())->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            $this->realUnlinker(freed: false),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_DELTER_DECLINED, $outcome->reason());
        self::assertFalse($outcome->freedBytes(), 'a deleter saying false is never a freed report');
        self::assertCount(1, $this->unlinked, 'the deleter WAS asked here — unlike the already-gone arm above');
        self::assertTrue(is_file($this->path), 'and the bytes really are still there, so refusing was the truth');
    }

    /**
     * A two-parameter deleter keeps working. The verified hash is additive
     * (a third argument PHP simply does not bind), so every existing deleter
     * written against the old two-argument signature still frees correctly.
     *
     * Threat pinned: the content proof being a BREAKING change to a published
     * seam. A host that wrote `fn ($disk, $path) => unlink($path)` before this
     * release must keep working without a code change, or the safety fix ships
     * as an outage on upgrade day.
     *
     * Falsified: this arm is what the "extra arguments to userland callables
     * are ignored" claim rests on; an ArgumentCountError here is the exact
     * regression, and the file's real unlink in the assertions is what proves
     * it still ran.
     */
    public function test_a_two_parameter_deleter_still_frees_the_file(): void
    {
        $msgId = $this->remoteDocument();
        $freed = null;

        $outcome = $this->service(ReferencePolicy::coldRemoteAuthoritative())->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => $msgId],
            null,
            function (string $disk, string $path) use (&$freed): bool {
                $freed = [$disk, $path];

                return unlink($path);
            },
        );

        self::assertTrue($outcome->freedBytes(), implode(' ', [$outcome->state(), $outcome->reason()]));
        self::assertSame(['local', $this->path], $freed, 'the legacy two-argument deleter ran, and the extra hash was ignored');
        self::assertFalse(is_file($this->path), 'and it really unlinked');
    }
}
