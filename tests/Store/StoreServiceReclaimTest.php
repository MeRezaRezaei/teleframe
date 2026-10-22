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
 * StoreService::reclaimLocal under the LOCAL posture
 * (Cycle 16 W2, verbatim 2026-09-24: "since we delete something in main
 * laravel storage but its hash in telegram is still in database and the file
 * is in the channel we can store big data and keep our hot data and cold data
 * intact"; proof hardening 2026-09-27).
 *
 * This suite is the LOCAL half — `ReferencePolicy::dropLocalAfterIndex()`,
 * which never consults the wire — and so is the complement of
 * ColdCopyReclaimTest, which is the COLD half and resolves its proof from
 * Telegram. Between them every posture the policy can be in is covered.
 *
 * WHAT CHANGED UNDER THE LOCAL POSTURE (2026-09-27)
 *
 * The proof used to be REFERENCE-shaped: "document 777 is addressable" said
 * nothing whatsoever about the bytes at $path, and a bare row handed in by
 * the caller was accepted on its own testimony, with no lookup and no wire
 * call. That is the hole this suite now pins shut from four directions:
 *
 *   1. IDENTITY — a reclaim must name an OBJECT ($expect[bucket] +
 *     $expect[msg_id]); row-shaped keys a caller supplies are ignored, and
 *     the row is always resolved from the store.
 *   2. ADDRESSABILITY — the resolved row must carry a non-empty
 *     file_reference, a positive doc_id and a positive access_hash.
 *   3. CONTENT — a sha256 must bind those document bytes to the file actually
 *     on disk, and the file's own sha256 must equal it.
 *   4. FRESHNESS is the cold posture's arm; here it must be unreachable, and
 *     one test proves the local posture never even asks the wire.
 *
 * THE FIXTURE IS A REAL FILE, ON PURPOSE
 *
 * Because the store now hashes the local file, every test that expects a
 * reclaim to SUCCEED needs bytes that actually hash to the value the proof
 * expects. So setUp() writes a real temp file, and indexMedia() defaults the
 * row's `content_sha256` to that file's real sha256 — the default is the proof
 * a reclaim needs, and any test that departs from it says so at the call site
 * with a named argument. A test that reclaimed a path which does not exist was
 * only ever able to pass because the store never looked.
 *
 * The deleter is still an injected recorder, not `unlink()`: what is asserted
 * here is the DECISION and the arguments the deleter is handed, and the file
 * is still on disk after a "reclaimed" outcome. That the bytes really leave the
 * disk is ColdCopyReclaimTest's claim, over there, with a real unlink — this
 * file never claims it, and the success tests assert the boundary out loud so
 * a future reader cannot mistake one for the other.
 *
 * The load-bearing test remains test_republish_still_works_after_the_local_file_is_freed:
 * the whole reason deleting is safe is that the hash row alone can still
 * re-publish the document. If reclaim succeeds and republish then needs the
 * local bytes, the feature destroys data — so that ordering is asserted
 * explicitly rather than inferred.
 */
final class StoreServiceReclaimTest extends TestCase
{
    private const ACCOUNT = 7;

    private const BUCKET = 'data:pics';

    /** The bytes written into the temp file every setUp() creates. */
    private const CONTENTS = "a real local file's bytes\n";

    /**
     * Default for indexMedia()'s $contentSha256: "use the temp file's own
     * sha256", i.e. a row that is genuinely proven against the bytes on disk.
     * A test that wants the row UNPROVEN passes null explicitly, and a test
     * that wants it wrong passes a 64-hex string that is not the file's.
     */
    private const SELF_HASH = "\0sha256-of-the-temp-file\0";

    /** A syntactically valid sha256 that is NOT this file's — a decoy binding. */
    private const FOREIGN_HASH = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private FakeStoreApi $api;

    private MemoryStoreIndex $index;

    private StoreService $service;

    /** The real temp file the store is asked to hash. */
    private string $path = '';

    /** The real sha256 of self::CONTENTS, i.e. of the file at $this->path. */
    private string $sha = '';

    /** @var list<array{0: string, 1: string}> */
    private array $unlinked = [];

    /** @var list<string> the third argument each deleter call was handed */
    private array $verified = [];

    protected function setUp(): void
    {
        $this->api = new FakeStoreApi(scope: 'svc-reclaim');
        $this->index = new MemoryStoreIndex;
        $this->unlinked = [];
        $this->verified = [];

        $this->path = (string) tempnam(sys_get_temp_dir(), 'tfstore-local-');
        file_put_contents($this->path, self::CONTENTS);
        $this->sha = hash('sha256', self::CONTENTS);

        $this->service = $this->store();
    }

    protected function tearDown(): void
    {
        if ($this->path !== '' && is_file($this->path)) {
            unlink($this->path);
        }
    }

    /**
     * A service over the same index/cache/queue, with the wire optionally
     * replaced call-for-call so a test can COUNT what the store asked it.
     *
     * @param  array<string, callable>  $overrides
     */
    private function store(array $overrides = []): StoreService
    {
        $map = $overrides + $this->api->apiMap();

        return new StoreService(
            accountId: self::ACCOUNT,
            channelFactory: fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $map),
            index: $this->index,
            cache: new MemoryHotCache(ttlSeconds: 600, maxBytes: 1024 * 1024),
            queue: new SyncUploadQueue,
        );
    }

    /**
     * The injected deleter: records the call, the hash it was handed, and
     * reports the bytes as freed (or not).
     */
    private function unlinker(bool $freed = true): callable
    {
        return function (string $disk, string $path, ?string $verified = null) use ($freed): bool {
            $this->unlinked[] = [$disk, $path];
            $this->verified[] = (string) $verified;

            return $freed;
        };
    }

    /**
     * The same recorder with the OLD two-parameter shape. Kept because the
     * third argument is documented as additive: a deleter written before the
     * content proof must keep working untouched.
     */
    private function twoParameterUnlinker(): callable
    {
        return function (string $disk, string $path): bool {
            $this->unlinked[] = [$disk, $path];

            return true;
        };
    }

    /**
     * Index one media row for (ACCOUNT, BUCKET, $msgId).
     *
     * $contentSha256 DEFAULTS TO THE TEMP FILE'S OWN sha256. That default is
     * the point of the fixture: the common case in this file is a row whose
     * bytes are genuinely proven, and burying that behind a helper call would
     * hide the single fact every success path depends on. Passing
     * `contentSha256: null` is therefore a DEVIATION and says so.
     */
    private function indexMedia(
        int $msgId,
        string $fileReference = 'AQAD-file-reference-bytes',
        int $docId = 777,
        int $accessHash = 123456789,
        ?string $contentSha256 = self::SELF_HASH,
    ): void {
        $this->index->upsertMedia(self::ACCOUNT, self::BUCKET, $msgId, [
            'media_type' => 'document',
            'doc_id' => $docId,
            'access_hash' => $accessHash,
            'file_reference' => $fileReference,
            'size' => 4096,
            'mime' => 'image/jpeg',
            'content_sha256' => $contentSha256 === self::SELF_HASH ? $this->sha : $contentSha256,
            'indexed_at_unix' => 1758000000,
        ]);
    }

    public function test_default_policy_never_touches_the_filesystem(): void
    {
        $this->indexMedia(42);

        $outcome = $this->service->reclaimLocal('local', '/storage/app/pics/42.jpg', ['bucket' => self::BUCKET, 'msg_id' => 42]);

        self::assertTrue($outcome->isRetained());
        self::assertSame(ReclaimOutcome::REASON_POLICY_RETAINS, $outcome->reason());
        self::assertSame([], $this->unlinked, 'the default policy must not even attempt a delete');
    }

    /**
     * The happy path, under the dropping posture, with a REAL file whose bytes
     * are the bytes the row claims: the store hashes the file, the hash agrees,
     * and the deleter is reached.
     *
     * Falsified by breaking the content comparison (e.g. reverting the
     * `hash_equals($expected, $actual)` check): the outcome was already
     * RECLAIMED before this fixture existed, so the assertion that matters is
     * the third argument below — the deleter must be handed the sha256 the
     * store just verified, not a re-derivation or nothing at all.
     */
    public function test_drop_local_frees_the_file_once_the_bytes_are_proven(): void
    {
        $this->indexMedia(42);
        self::assertTrue(is_file($this->path), 'precondition: there are real local bytes to free');

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->freedBytes());
        self::assertSame(ReclaimOutcome::RECLAIMED, $outcome->state());
        self::assertSame(ReclaimOutcome::REASON_DELETED, $outcome->reason());
        self::assertSame([['local', $this->path]], $this->unlinked);
        self::assertSame(
            [$this->sha],
            $this->verified,
            'the deleter is handed the sha256 the store verified, so the last hop can re-check it',
        );
        self::assertTrue(
            is_file($this->path),
            'this suite\'s deleter RECORDS; the file is still here. On-disk disappearance is ColdCopyReclaimTest\'s claim, not this one',
        );
    }

    /**
     * The third argument is additive: a deleter written against the old
     * two-parameter shape is not a caller error and must still free.
     *
     * Falsified by passing the verified hash positionally into a two-parameter
     * closure through a strict wrapper, or by the store ever switching to
     * reflection/`Closure::call` semantics that reject surplus arguments.
     */
    public function test_a_two_parameter_deleter_still_reclaims(): void
    {
        $this->indexMedia(42);

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->twoParameterUnlinker(),
        );

        self::assertTrue($outcome->freedBytes());
        self::assertSame(ReclaimOutcome::RECLAIMED, $outcome->state());
        self::assertSame([['local', $this->path]], $this->unlinked);
    }

    /**
     * The core safety property: reclaim first, then re-publish. If the local
     * file is gone and publishByReference still needs zero uploads, the hash
     * row really did stand in for the bytes.
     */
    public function test_republish_still_works_after_the_local_file_is_freed(): void
    {
        $this->indexMedia(42);

        $reclaim = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );
        self::assertTrue($reclaim->freedBytes());

        $msgId = $this->service->publishByReference(
            self::BUCKET,
            42,
            ['_' => 'inputPeerChannel', 'channel_id' => 900, 'access_hash' => 4242],
            'after the local file is gone',
        );

        self::assertGreaterThan(0, $msgId);
        self::assertSame(0, $this->api->uploadPartCalls, 'republish after reclaim must still upload nothing');
        self::assertCount(1, $this->api->referenceSends);
        self::assertSame('AQAD-file-reference-bytes', $this->api->referenceSends[0]['media']['id']['file_reference']);
    }

    public function test_empty_file_reference_is_refused_and_nothing_is_deleted(): void
    {
        $this->indexMedia(42, fileReference: '');

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_EMPTY_FILE_REFERENCE, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked, 'an unusable reference means the local file is the only copy');
        self::assertTrue(is_file($this->path));
    }

    public function test_non_positive_doc_id_is_refused_and_nothing_is_deleted(): void
    {
        $this->indexMedia(42, docId: 0);

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_INVALID_DOC_ID, $outcome->reason());
        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD: addressability, third arm. `access_hash === 0` is MTProto's
     * "unknown peer" sentinel — a document with one cannot be named by ANY
     * peer, so no publishByReference() could ever re-post it. A reclaim that
     * accepted it would prove durability for a document that is unreachable by
     * construction, which is exactly the "the local file is the only copy"
     * situation wearing a success.
     *
     * Falsified by deleting the `accessHash <= 0` check from reclaimLocal():
     * the row then reads as a fully proven local copy, the file's real sha256
     * matches the row, and the outcome flips to RECLAIMED with the deleter
     * called.
     */
    public function test_a_zero_access_hash_is_refused_and_nothing_is_deleted(): void
    {
        $this->indexMedia(42, accessHash: 0);

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_INVALID_ACCESS_HASH, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked, 'an unnameable document is not durability');
        self::assertTrue(is_file($this->path));
    }

    /**
     * Nothing indexed: the local file is the ONLY copy, so this must throw
     * rather than return an outcome a caller could mistake for success. The
     * deleter must be UNREACHABLE, so the post-state is asserted after the
     * catch rather than through expectException().
     */
    public function test_missing_index_row_fails_loudly_rather_than_freeing_the_only_copy(): void
    {
        try {
            $this->service->reclaimLocal(
                'local',
                $this->path,
                ['bucket' => self::BUCKET, 'msg_id' => 99],
                ReferencePolicy::dropLocalAfterIndex(),
                $this->unlinker(),
            );
            self::fail('an unindexed local file is the only copy and must not be freed');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('the local file is the only copy', $e->getMessage());
        }

        self::assertSame([], $this->unlinked, 'the throw must happen before the deleter is reachable');
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD: dry run decides on the FULL proof, then stops. The file is real
     * and the row is genuinely proven here, so the only thing separating
     * REASON_DRY_RUN from RECLAIMED is the flag — a dry run that short-circuited
     * before the content check would still pass, which is why the file and the
     * hash are real and why the deleter must not even be asked.
     */
    public function test_dry_run_decides_without_deleting(): void
    {
        $this->indexMedia(42);

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
            dryRun: true,
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_DRY_RUN, $outcome->reason());
        self::assertFalse($outcome->freedBytes(), 'a dry run must never claim it freed bytes');
        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD: a path with nothing on it. The store has no bytes to hash, so
     * there is no data at risk — and equally no proof, so the deleter is NOT
     * asked to act on a path whose contents were never verified.
     *
     * Falsified by moving the `is_file()` check after the deleter call, or by
     * returning a RECLAIMED outcome for a missing file: either makes a reclaim
     * report work it did not do.
     */
    public function test_a_path_that_is_already_gone_is_refused_without_asking_the_deleter(): void
    {
        $this->indexMedia(42);
        unlink($this->path);
        self::assertFalse(is_file($this->path), 'precondition: the local copy is already gone');

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_ALREADY_GONE, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked, 'nothing was verified, so the deleter must stay unreachable');
    }

    /**
     * A deleter that reports the bytes were NOT freed is never reported as
     * freed. Distinct from the arm above: here the file WAS verified and the
     * deleter WAS asked, and it said no.
     */
    public function test_a_deleter_that_refuses_is_never_reported_as_freed(): void
    {
        $this->indexMedia(42);

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(freed: false),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_DELTER_DECLINED, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertCount(1, $this->unlinked, 'the deleter was still asked, and said no');
        self::assertSame([$this->sha], $this->verified, 'and it was told which bytes it was being asked about');
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD: identity, the negative form. This is the hole this file used to
     * ASSERT: a bare, invented row in $expect freed a real file with no lookup
     * and no wire call, on the row's own testimony. Now a reclaim must name an
     * OBJECT, so a row-shaped $expect that names none is rejected outright and
     * the deleter is never reached.
     *
     * The keys below are deliberately the ones that used to be enough —
     * doc_id and file_reference — so this test reads as the old hole.
     *
     * Falsified by relaxing the identity check back to "a row-shaped $expect
     * will do": the file's bytes then need not match anything, and the outcome
     * comes back RECLAIMED.
     */
    public function test_a_bare_row_shaped_expect_is_rejected_and_never_frees_the_file(): void
    {
        try {
            $this->service->reclaimLocal(
                'local',
                $this->path,
                ['doc_id' => 777, 'file_reference' => 'AQAD-inline-row', 'content_sha256' => $this->sha],
                ReferencePolicy::dropLocalAfterIndex(),
                $this->unlinker(),
            );
            self::fail('a bare row is a claim about bytes, not about a named object');
        } catch (InvalidArgumentException $e) {
            self::assertStringStartsWith(
                'reclaimLocal needs $expect[bucket] and $expect[msg_id] naming the object',
                $e->getMessage(),
            );
        }

        self::assertSame([], $this->unlinked, 'a rejected expect must not leave the deleter reachable');
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD: identity, the positive form — the caller-supplied row is IGNORED
     * even when it looks perfect, and only the INDEXED row decides. Here the
     * caller asserts a healthy document (positive doc_id, a real
     * file_reference, and the file's actual sha256) while the indexed row for
     * that (bucket, msg_id) is unusable. Trusting the caller's claim yields
     * RECLAIMED; resolving the row yields the refusal below.
     *
     * Falsified by reading doc_id/file_reference from $expect when the row
     * lacks them (or by falling back to $expect whenever a key is present):
     * the outcome flips to RECLAIMED on the caller's testimony alone.
     */
    public function test_a_caller_supplied_row_is_ignored_and_the_indexed_row_decides(): void
    {
        $this->indexMedia(42, fileReference: '');

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            [
                'bucket' => self::BUCKET,
                'msg_id' => 42,
                'doc_id' => 999,
                'file_reference' => 'AQAD-a-perfectly-healthy-looking-claim',
                'content_sha256' => $this->sha,
            ],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_EMPTY_FILE_REFERENCE, $outcome->reason());
        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path));
    }

    /**
     * An empty $expect: the other face of the same identity guard, kept
     * separate because it is the shape a caller reaches for first.
     */
    public function test_an_empty_expect_is_rejected_before_any_lookup(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('needs $expect[bucket] and $expect[msg_id] naming the object');

        $this->service->reclaimLocal(
            'local',
            $this->path,
            [],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );
    }

    /**
     * GUARD: content, arm one — nothing binds these bytes to that document.
     * A row with `content_sha256 = NULL` and no caller binding is the NORMAL
     * shape of a row a chat message authored (archiveMessageMedia cannot hash
     * what it has not seen), and it is precisely the case that used to free a
     * local file on a stranger's testimony.
     *
     * Falsified by removing the `$expected === null` refusal: the addressable
     * reference then stands in for the content, the file's hash is never
     * compared against anything, and the outcome is RECLAIMED.
     */
    public function test_a_row_with_no_content_hash_keeps_the_file(): void
    {
        $this->indexMedia(42, contentSha256: null);

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_NO_CONTENT_PROOF, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked, 'no hash to check means no delete may be attempted');
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD: content, arm two — the row's hash is not the hash of the file
     * being deleted. This is the other half of the old hole: even a row that
     * DOES carry a sha256 proves nothing if the bytes at $path are somebody
     * else's. Deleting here would trade one stranger's file for a row.
     *
     * Falsified by removing the `hash_equals($expected, $actual)` check (or by
     * trusting the row's hash as evidence on its own): the outcome flips to
     * RECLAIMED and the unbacked file is gone.
     */
    public function test_a_row_whose_hash_is_not_the_hash_of_the_file_being_deleted_keeps_the_file(): void
    {
        self::assertNotSame(self::FOREIGN_HASH, $this->sha, 'precondition: the decoy binding is NOT this file\'s hash');
        $this->indexMedia(42, contentSha256: self::FOREIGN_HASH);

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_CONTENT_MISMATCH, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked, 'the deleter must not be asked about bytes it cannot vouch for');
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD: content, arm three — a disagreement is a refusal, never a
     * "pick the one that lets the delete through". Both bindings here are
     * present and both are well-formed sha256 values, so this is a pure
     * cross-check and nothing else in the method can produce this outcome.
     *
     * Falsified by preferring one side (`$asserted ?? $declared` without the
     * equality test, or dropping the conflict branch): with the row's real
     * hash winning, the file is freed; with the caller's decoy winning, the
     * call simply returns CONTENT_MISMATCH. Only the conflict branch is
     * unique to a genuine disagreement.
     */
    public function test_a_caller_hash_that_disagrees_with_the_row_is_a_binding_conflict(): void
    {
        $this->indexMedia(42); // the row is proven: content_sha256 is $this->sha

        $outcome = $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42, 'content_sha256' => self::FOREIGN_HASH],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->isRefused());
        self::assertSame(ReclaimOutcome::REASON_BINDING_CONFLICT, $outcome->reason());
        self::assertFalse($outcome->freedBytes());
        self::assertSame([], $this->unlinked);
        self::assertTrue(is_file($this->path));
    }

    /**
     * GUARD: the local posture must not reach the wire at all. The freshness
     * arms (REASON_REMOTE_DOCUMENT_GONE, REASON_REFERENCE_DRIFT) belong to the
     * COLD posture, and this suite is the local half. A `dropLocalAfterIndex`
     * reclaim that consulted the remote would be paying a full channel walk —
     * and a download of the document — to re-derive a proof the local row was
     * supposed to supply, on a posture whose entire reason for existing is that
     * the remote is not required.
     *
     * The wire's historyPage is replaced with a counter, so any consultation
     * shows up as a non-zero count rather than as a subtly different outcome.
     *
     * Falsified by making the freshness block depend on something other than
     * $policy->trustsRemote() (e.g. "always ask the remote, use the answer if
     * it agrees"): the count goes above zero and the reclaim still passes every
     * other assertion in this file.
     */
    public function test_the_local_posture_never_consults_the_wire(): void
    {
        $historyCalls = 0;
        $service = $this->store([
            'historyPage' => function (array $peer, int $limit, int $offsetId) use (&$historyCalls): array {
                $historyCalls++;

                return [];
            },
        ]);
        $this->indexMedia(42);

        $outcome = $service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );

        self::assertTrue($outcome->freedBytes());
        self::assertSame(0, $historyCalls, 'drop-local-after-index must be satisfiable without walking the channel');
        self::assertSame(0, $this->api->uploadPartCalls);
    }

    public function test_empty_path_is_rejected(): void
    {
        $this->indexMedia(42);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('path must not be empty');

        $this->service->reclaimLocal(
            'local',
            '   ',
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
            $this->unlinker(),
        );
    }

    /**
     * A missing deleter is loud — and it is loud only AFTER the proof passed,
     * which is why the file and the row here are real: the fixture proves this
     * test reaches the deleter check instead of stopping at an earlier
     * refusal and throwing for the wrong reason.
     */
    public function test_reclaiming_without_a_deleter_is_loud(): void
    {
        $this->indexMedia(42);
        self::assertTrue(is_file($this->path), 'precondition: the content proof can actually be completed');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('never reaches a disk directly');

        $this->service->reclaimLocal(
            'local',
            $this->path,
            ['bucket' => self::BUCKET, 'msg_id' => 42],
            ReferencePolicy::dropLocalAfterIndex(),
        );
    }
}
