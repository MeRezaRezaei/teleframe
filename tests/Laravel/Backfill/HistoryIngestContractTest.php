<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Backfill;

use MeRezaRezaei\Teleframe\Backfill\BackfillWorker;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcExceptionResolver;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;
use MeRezaRezaei\Teleframe\Tests\Support\ScriptedFetch;
use MeRezaRezaei\Teleframe\Tests\Support\SleepRecorder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/**
 * THE INGEST CONTRACT. Ingest has never landed a row: `SELECT COUNT(*) FROM
 * tf_messages` is 0 after real backfill runs, while the Telegram READ path
 * demonstrably works (messages.getHistory with a peer carrying access_hash
 * returned ids 12409-12413). So the fault is in the ingest path, and this
 * file pins the contract that path must satisfy.
 *
 * WHAT IS MEASURED (every claim below was read out of the shipped code, not
 * assumed):
 *
 *  - `UserAccountScope::normalizePeer()`
 *    (src/Core/Services/UserAccountScope.php:569) is a NO-OP: `return $peer;`.
 *    A peer that already carries an access_hash therefore arrives on the wire
 *    intact — good, and pinned below so a future "normalization" cannot start
 *    stripping it. But a bare numeric peer id ALSO arrives bare, with no
 *    access_hash, and Telegram rejects it. The command-line
 *    `--peer=<id>` form (src/Laravel/Console/BackfillCommand.php:94, :112)
 *    is exactly that form; `DialogDiscovery` by contrast builds the correct
 *    `['_'=>'inputPeerChannel','channel_id'=>…,'access_hash'=>…]`
 *    (src/Teleframe/Backfill/DialogDiscovery.php:165-174).
 *
 *  - `BackfillWorker::process()` catches a BARE `Throwable`
 *    (src/Teleframe/Backfill/BackfillWorker.php:117) and records the literal
 *    string `'fetch_failed'` (:122), discarding the exception. That is why a
 *    live run could only ever report {messages:0, status:dead,
 *    reason:fetch_failed} with no diagnosis: the one signal that would have
 *    named the missing access_hash was thrown away one line above the
 *    `return`.
 *
 *  - `UserAccountScope::getHistory()` (:178) sends a CORRECT eight-field param
 *    set (offset_id, offset_date, add_offset, limit, max_id, min_id, hash).
 *    The method call is NOT at fault, and is pinned here so a future fix is
 *    not aimed at the wrong line.
 *
 *  - MAX_ATTEMPTS is 5 (BackfillWorker.php:32), consulted at :106 and :121.
 *    A peer must survive four consecutive failures.
 *
 * COLD BY CONSTRUCTION. No database, no network, no Telegram, no .env, no
 * credentials. The scope is `FakeUserScope` (tests/Support/FakeUserScope.php),
 * whose `call()` answers from a script and never touches the transport, so
 * `getHistory()` returns without dialling. The command is instantiated
 * directly (`new BackfillCommand`) and `fetchPage()` is reached by reflection,
 * because it is the private seam (:646) that turns a getHistory answer into the
 * worker's page list. Nothing here can accidentally read a session string.
 *
 * NO TEST IS SKIPPED. Both defects this file was written to expose — the
 * hashless `--peer=<id>` reaching the wire, and the discarded exception behind
 * the constant `fetch_failed` — are fixed and are now enforced here as gates.
 * The measured lines quoted above are the PRE-FIX state and are kept as the
 * record of what was wrong; `BackfillCommand::resolvePeerArgument()` is the
 * collector-side resolution, and `BackfillWorker` folds the throwable's
 * identity into the recorded reason.
 */
final class HistoryIngestContractTest extends TestCase
{
    /** The peer shape DialogDiscovery produces (DialogDiscovery.php:170-171). */
    private const CHANNEL_PEER = [
        '_' => 'inputPeerChannel',
        'channel_id' => -1001234567890,
        'access_hash' => '99887766554433221100',
    ];

    // ---------------------------------------------------------------------
    // 1. The peer reaches the wire intact. normalizePeer() is a no-op, and
    //    that no-op is CORRECT for a peer that already carries a hash — so
    //    this is the regression guard that keeps it correct.
    // ---------------------------------------------------------------------

    /**
     * A peer array carrying an access_hash must pass through normalizePeer()
     * UNCHANGED — same keys, same order, same values, nothing stripped, nothing
     * rewritten, nothing appended. It is the last stop before the wire
     * (getHistory() at :181 is its only caller on this path), so any mutation
     * here is a peer Telegram cannot resolve.
     */
    public function test_peer_carrying_access_hash_survives_normalize_unchanged(): void
    {
        $normalized = $this->normalizePeer(self::CHANNEL_PEER);

        self::assertSame(
            self::CHANNEL_PEER,
            $normalized,
            'normalizePeer() must return a peer carrying an access_hash byte-identical: it is a no-op '
            .'(src/Core/Services/UserAccountScope.php:569-571) and must stay one, or the hash is lost and '
            .'messages.getHistory is rejected.',
        );
        self::assertSame(
            '99887766554433221100',
            $normalized['access_hash'] ?? null,
            'the access_hash must still be present and byte-identical after normalization',
        );
        self::assertSame(
            -1001234567890,
            $normalized['channel_id'] ?? null,
            'the channel_id must not be rewritten during normalization',
        );
        self::assertSame('inputPeerChannel', $normalized['_'] ?? null, 'the constructor must not be swapped');
    }

    /**
     * The same guarantee for a user peer, asserted separately because a
     * channel and a user fail differently on the wire: a user peer without a
     * hash is USER_ID_INVALID, a channel peer without one is CHANNEL_INVALID.
     * A normalizer that only learned to fix one shape would pass the channel
     * case and silently break the user case.
     */
    public function test_user_peer_carrying_access_hash_survives_normalize_unchanged(): void
    {
        $userPeer = ['_' => 'inputPeerUser', 'user_id' => 777, 'access_hash' => '1122334455667788'];

        self::assertSame(
            $userPeer,
            $this->normalizePeer($userPeer),
            'inputPeerUser + access_hash must pass through unchanged, exactly as inputPeerChannel does',
        );
    }

    /**
     * The peer array is ACCEPTED, not merely tolerated: getHistory() must place
     * the whole array — hash included — into the 'peer' param it hands to
     * call(), and must send the full eight-field set the schema requires.
     * Asserting the params, not the return value, is what makes this a wire
     * contract: the no-op is unobservable anywhere else.
     */
    public function test_get_history_places_the_peer_array_and_its_access_hash_on_the_wire(): void
    {
        $scope = new FakeUserScope;

        $scope->getHistory(self::CHANNEL_PEER, 50, 0);

        self::assertCount(1, $scope->calls, 'one getHistory, answered from the script — no transport');
        self::assertSame('messages.getHistory', $scope->calls[0]['method']);

        $params = $scope->calls[0]['params'];

        self::assertSame(
            self::CHANNEL_PEER,
            $params['peer'] ?? null,
            'the peer param on the wire must be the full array WITH access_hash',
        );
        self::assertArrayHasKey('access_hash', $params['peer'] ?? [], 'the hash must be on the wire, not only in the caller');
        self::assertSame(
            ['peer', 'offset_id', 'offset_date', 'add_offset', 'limit', 'max_id', 'min_id', 'hash'],
            array_keys($params),
            'getHistory must send exactly the eight params the schema declares (UserAccountScope.php:180-189); '
            .'the method call is not at fault and must not be "fixed"',
        );
    }

    /**
     * THE ACCEPTANCE BLOCKER, NOW A GATE.
     *
     * `--peer=-1001234567890` is documented as a supported form
     * (BackfillCommand.php:94) and collected verbatim, so it used to reach
     * fetchPage() (:646) as a bare int. normalizePeer() is a no-op (:569), so
     * the bare int reached the wire with no access_hash and Telegram rejected
     * it — the run reported dead, zero messages, forever.
     *
     * The behaviour pinned here is the production seam itself: handle()
     * collects `--peer`, resolves it through `BackfillCommand::resolvePeerArgument()`
     * into `$inputPeers`, and `:269` reads `$inputPeers[$peer] ?? $peer` — so
     * the value that goes on the wire IS what is asserted, not a re-derivation.
     *
     * The lookup passed in is the mirror index the command builds from the
     * shipped `tf_channels` / `tf_users_access_hash` / `tf_chats` rows (the
     * same hashes DialogDiscovery reads out of a dialogs page), so the shape
     * asserted here is the shape that reaches Telegram in production.
     */
    public function test_a_bare_numeric_peer_never_reaches_the_wire_without_an_access_hash(): void
    {
        $scope = new FakeUserScope;

        $resolved = BackfillCommand::resolvePeerArgument(
            '-1001234567890',
            static fn (int $peerId): ?array => [
                '_' => 'inputPeerChannel',
                'channel_id' => $peerId,
                'access_hash' => '99887766554433221100',
            ],
        );

        self::assertIsArray(
            $resolved,
            'a bare --peer=<id> must not be sent to Telegram as a bare id: it carries no access_hash and is '
            .'always rejected. It must be resolved to a peer array with a hash, or refused before the request.',
        );
        self::assertArrayHasKey(
            'access_hash',
            $resolved,
            'the resolved peer must carry an access_hash, or messages.getHistory rejects it '
            .'(compare DialogDiscovery.php:165-174, which builds the hash correctly).',
        );

        // And the RESOLVED value — not the bare id — is what the wire receives.
        $this->fetchPage($scope, $resolved, 0, 50);

        $onTheWire = $scope->calls[0]['params']['peer'] ?? null;

        self::assertIsArray($onTheWire, 'the resolved peer must reach getHistory as an array, not the raw --peer string');
        self::assertArrayHasKey('access_hash', $onTheWire, 'the hash must survive resolution AND reach the wire');
        self::assertSame(-1001234567890, $onTheWire['channel_id'] ?? null, 'resolution must not rewrite the id');
    }

    /**
     * The other half of the contract, and the half that costs the operator
     * nothing: a bare id the account has NO hash for must be refused BEFORE a
     * request is spent. Refusing is not a fallback — it is the outcome that
     * stops the run from reporting a generic fetch failure five times for a
     * peer that could never have succeeded.
     *
     * `$scope->calls` staying empty is the assertion that makes this a gate
     * rather than a message: a refusal that still dialled would spend a
     * request on a peer already known to be unresolvable.
     */
    public function test_an_unresolvable_numeric_peer_is_refused_before_any_request_is_spent(): void
    {
        $scope = new FakeUserScope;

        $refusal = null;

        try {
            BackfillCommand::resolvePeerArgument('-1001234567890', static fn (): ?array => null);
        } catch (Throwable $e) {
            $refusal = $e;
        }

        self::assertInstanceOf(
            RuntimeException::class,
            $refusal,
            'a numeric --peer the account has no access_hash for must be REFUSED, not dialled as a bare id',
        );
        self::assertStringContainsString(
            BackfillCommand::PEER_UNRESOLVED,
            (string) $refusal?->getMessage(),
            'the refusal must carry a named reason an operator can grep for, not prose',
        );
        self::assertStringContainsString(
            '--full',
            (string) $refusal?->getMessage(),
            'the refusal must be ACTIONABLE: --full already carries access hashes (BackfillCommand.php:153)',
        );
        self::assertSame(
            [],
            $scope->calls,
            'no request may be spent on a peer already known to be unresolvable',
        );
    }

    /**
     * ADDITIVE, NOT REPLACING. An array peer — the shape DialogDiscovery
     * builds and `--full` feeds in — must survive the new resolution step
     * BYTE-IDENTICAL, and the mirror index must not even be consulted for it:
     * the shipped hashes are already correct and re-deriving them could only
     * lose them. The lookup throws if called, which is what proves "not even
     * consulted" rather than merely "consulted and agreed".
     */
    public function test_an_array_peer_argument_passes_through_byte_identical_without_a_lookup(): void
    {
        $resolved = BackfillCommand::resolvePeerArgument(
            self::CHANNEL_PEER,
            static function (): array {
                throw new RuntimeException('the mirror index must not be consulted for an array peer');
            },
        );

        self::assertSame(
            self::CHANNEL_PEER,
            $resolved,
            'an array peer is already constructed and already correct: it must pass through untouched',
        );
    }

    // ---------------------------------------------------------------------
    // 2. The failure reason must be able to SAY WHAT WENT WRONG.
    // ---------------------------------------------------------------------

    /**
     * The information the worker needs in order to diagnose a missing
     * access_hash already exists and is already typed: a hashless peer comes
     * back as an RpcErrorException carrying the raw wire error. This test is
     * the supporting fact for the skipped contract below — if the worker ever
     * cannot tell the two apart, the fault will be here, not in the resolver.
     */
    public function test_a_hashless_peer_rejection_is_available_as_a_typed_rpc_error(): void
    {
        $rejection = RpcExceptionResolver::resolve('CHANNEL_INVALID', 400, 'messages.getHistory');

        self::assertInstanceOf(
            RpcErrorException::class,
            $rejection,
            'Telegram\'s answer to a peer it cannot resolve must arrive as a typed RPC error, not a bare string',
        );
        self::assertSame(
            'CHANNEL_INVALID',
            $rejection->rpcErrorMessage,
            'the wire error name must survive resolution — it is the only thing that distinguishes a '
            .'missing access_hash from an unrelated RPC failure',
        );
    }

    /**
     * NOW A GATE. The defect was real: BackfillWorker::process() caught a bare
     * `Throwable` and returned the constant 'fetch_failed', discarding the
     * exception — the only carrier of the wire error name. Every hashless peer
     * and every genuine RPC error therefore produced the IDENTICAL report,
     * which is exactly why the live run could not be diagnosed.
     *
     * The reason now folds the exception's identity in, so the wire error name
     * survives into both the console line and the persisted
     * `tf_backfill_cursors.reason`.
     */
    public function test_the_recorded_fetch_failure_reason_can_distinguish_a_missing_access_hash(): void
    {
        $worker = new BackfillWorker(100, 3600, new SleepRecorder);

        // What Telegram actually answers when the peer has no access_hash.
        $missingHash = array_fill(0, BackfillWorker::MAX_ATTEMPTS, RpcExceptionResolver::resolve(
            'CHANNEL_INVALID',
            400,
            'messages.getHistory',
        ));
        $hashResult = $worker->process(
            'channel:-1001234567890',
            new ScriptedFetch($missingHash),
            static function (): void {},
        );

        // A genuine, unrelated RPC error on the very same path.
        $genuineError = array_fill(0, BackfillWorker::MAX_ATTEMPTS, RpcExceptionResolver::resolve(
            'CHAT_WRITE_FORBIDDEN',
            403,
            'messages.getHistory',
        ));
        $genuineResult = $worker->process(
            'channel:-1001234567890',
            new ScriptedFetch($genuineError),
            static function (): void {},
        );

        self::assertSame('dead', $hashResult['status'], 'the run is expected to end dead — the point is the REASON');
        self::assertSame('dead', $genuineResult['status']);

        self::assertNotSame(
            'fetch_failed',
            $hashResult['reason'],
            'a reason of "fetch_failed" is not a diagnosis: BackfillWorker.php:122 discards the exception at '
            .':117, so a missing access_hash and a write-forbidden chat are indistinguishable.',
        );
        self::assertStringContainsString(
            'CHANNEL_INVALID',
            (string) $hashResult['reason'],
            'the recorded reason must name the wire error, so an operator can see the peer was rejected for '
            .'lack of a hash rather than for any other reason',
        );
        self::assertNotSame(
            $hashResult['reason'],
            $genuineResult['reason'],
            'two different RPC failures on the same peer must record two different reasons, or the run is '
            .'undiagnosable — which is what happened live.',
        );
    }

    // ---------------------------------------------------------------------
    // 3. fetchPage() is the seam that turns a getHistory answer into pages.
    // ---------------------------------------------------------------------

    /**
     * fetchPage() must hand the worker the `messages` array of the response,
     * unmodified. It is the ONLY place between the wire and the worker's
     * `$page` (BackfillCommand.php:646-654, wired at :273-274), so a wrong
     * read here starves ingest even when the fetch succeeds — which is the
     * failure mode that leaves tf_messages empty with no error anywhere.
     */
    public function test_fetch_page_returns_the_messages_array_from_a_get_history_response(): void
    {
        $messages = [
            ['id' => 12413, '_' => 'message', 'message' => 'newest', 'date' => 1_700_000_013, 'peer_id' => ['_' => 'peerChannel', 'channel_id' => 123]],
            ['id' => 12412, '_' => 'message', 'message' => 'next', 'date' => 1_700_000_012, 'peer_id' => ['_' => 'peerChannel', 'channel_id' => 123]],
        ];
        $scope = $this->cannedHistoryScope(['_' => 'messages.channelMessages', 'count' => 2, 'messages' => $messages]);

        $page = $this->fetchPage($scope, self::CHANNEL_PEER, 0, 50);

        self::assertSame(
            $messages,
            $page,
            'fetchPage() must return the response\'s `messages` array verbatim, in wire order — the worker '
            .'reads $page[count-1]["id"] as the next offset (BackfillWorker.php:138), so a reordered or '
            .'rebuilt page would corrupt the cursor.',
        );
    }

    /**
     * An empty page — history exhausted — must come back as [], which is the
     * worker's ONLY signal to end a run as 'done' (BackfillWorker.php:130-132).
     * A null, or a response with no messages key, would either TypeError or
     * keep the run looping.
     */
    public function test_fetch_page_returns_an_empty_list_for_an_empty_page(): void
    {
        $scope = $this->cannedHistoryScope(['_' => 'messages.channelMessages', 'count' => 0, 'messages' => []]);

        self::assertSame(
            [],
            $this->fetchPage($scope, self::CHANNEL_PEER, 12409, 50),
            'an empty page must be [], never null: BackfillWorker.php:130 compares the page to [] exactly',
        );
    }

    /**
     * fetchPage() must forward the caller's cursor and page size in the
     * argument slots getHistory() declares — getHistory(peer, limit, offsetId)
     * at UserAccountScope.php:178, called positionally at BackfillCommand.php:648.
     * A swapped pair would page the wrong window and still return a plausible
     * array, so it can only be caught by asserting the params.
     */
    public function test_fetch_page_forwards_the_cursor_and_page_size_to_get_history(): void
    {
        $scope = $this->cannedHistoryScope(['_' => 'messages.channelMessages', 'count' => 0, 'messages' => []]);

        $this->fetchPage($scope, self::CHANNEL_PEER, 12409, 37);

        $params = $scope->calls[0]['params'] ?? [];
        self::assertSame(12409, $params['offset_id'] ?? null, 'the resume cursor must land in offset_id');
        self::assertSame(37, $params['limit'] ?? null, 'the page size must land in limit');
        self::assertSame(
            self::CHANNEL_PEER,
            $params['peer'] ?? null,
            'the peer with its access_hash must be the peer that is fetched',
        );
    }

    // ---------------------------------------------------------------------
    // 4. A peer is not dead on its first failure.
    // ---------------------------------------------------------------------

    /**
     * MAX_ATTEMPTS is the contract's number (BackfillWorker.php:32, consulted
     * at :106 and :121). Asserted so a change to it is a deliberate, visible
     * one: it is the threshold at which a resumable peer becomes dead-lettered.
     */
    public function test_max_attempts_is_five(): void
    {
        self::assertSame(5, BackfillWorker::MAX_ATTEMPTS, 'five consecutive failed fetches dead-letter a peer');
    }

    /**
     * A FLOOD_WAIT is retried on the SAME offset and does NOT kill the peer.
     * Flooding is throttling, not rejection: a peer that died on a flood would
     * be dead-lettered by a rate limit it could have waited out, which is a
     * self-inflicted loss of history.
     */
    public function test_a_flood_wait_is_retried_and_does_not_kill_the_peer(): void
    {
        $sleep = new SleepRecorder;
        $worker = new BackfillWorker(100, 3600, $sleep);

        $fetch = new ScriptedFetch([
            new FloodWaitException(3, 'FLOOD_WAIT_3', 420),
            new FloodWaitException(3, 'FLOOD_WAIT_3', 420),
            ScriptedFetch::page(12413, 12412),
            [],
        ]);

        $result = $worker->process('channel:-1001234567890', $fetch, static function (): void {});

        self::assertSame('done', $result['status'], 'two floods must not end the run — the peer is resumable');
        self::assertNull($result['reason'], 'a run that recovered has no failure reason');
        self::assertSame(2, $result['messages']);
        self::assertCount(6, $sleep->seconds, 'both floods slept 3s, chunked into 1s slices');
        self::assertSame(
            [[0, 50], [0, 49], [0, 48], [12412, 47]],
            $fetch->calls,
            'each flood retried the SAME offset (0) — a flood must never advance the cursor — and the cursor '
            .'advanced to the oldest fetched id only after a successful page',
        );
    }

    /**
     * Four consecutive failures — one short of MAX_ATTEMPTS — must still leave
     * the peer alive, and a success on the fifth attempt must end the run 'done'
     * with the failures forgotten. This is the "does NOT mark the peer dead on
     * the first failure" half of the contract, stated at its exact boundary:
     * the streak is reset by a success (BackfillWorker.php:134), so a peer
     * that fails four times and then works is not dead-lettered.
     */
    public function test_a_peer_survives_four_consecutive_failures_and_recovers(): void
    {
        $worker = new BackfillWorker(100, 3600, new SleepRecorder);

        $failures = array_fill(
            0,
            BackfillWorker::MAX_ATTEMPTS - 1,
            RpcExceptionResolver::resolve('CHANNEL_INVALID', 400, 'messages.getHistory'),
        );
        $fetch = new ScriptedFetch([...$failures, ScriptedFetch::page(12413), []]);

        $result = $worker->process('channel:-1001234567890', $fetch, static function (): void {});

        self::assertSame(
            'done',
            $result['status'],
            'four failures then a success is a completed peer, not a dead one: MAX_ATTEMPTS is 5, and a '
            .'success resets the streak (BackfillWorker.php:134).',
        );
        self::assertSame(1, $result['messages'], 'the recovered run still ingests the page it finally got');
        self::assertSame(12413, $result['offset_id'], 'the cursor ends on the oldest id actually fetched');
        self::assertCount(6, $fetch->calls, 'five attempts (four failures plus the success) then the empty page');
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Reach the protected normalizePeer() directly
     * (src/Core/Services/UserAccountScope.php:569). It is the last stop before
     * the wire, so it is the only place its contract is observable in
     * isolation; getHistory() proves the same thing end-to-end, above.
     *
     * @param  int|string|array<string, mixed>  $peer
     * @return int|string|array<string, mixed>
     */
    private function normalizePeer(int|string|array $peer): int|string|array
    {
        $method = new ReflectionMethod(UserAccountScope::class, 'normalizePeer');

        return $method->invoke(new FakeUserScope, $peer);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchPage(UserAccountScope $scope, int|string|array $peer, int $offsetId, int $limit): array
    {
        $command = new BackfillCommand;
        $method = new ReflectionMethod(BackfillCommand::class, 'fetchPage');
        $method->setAccessible(true);

        return $method->invoke($command, $scope, $peer, $offsetId, $limit);
    }

    /**
     * A scope that answers messages.getHistory with one canned response,
     * recording the params it was called with. Built on the repo's existing
     * offline double (tests/Support/FakeUserScope.php), which constructs a
     * SessionData but never dials.
     *
     * @param  array<string, mixed>  $response
     */
    private function cannedHistoryScope(array $response): UserAccountScope
    {
        return new class($response) extends FakeUserScope
        {
            /** @param array<string, mixed> $response */
            public function __construct(private readonly array $response)
            {
                parent::__construct();
            }

            public function call(string $method, array $params = []): array
            {
                $this->calls[] = ['method' => $method, 'params' => $params];

                if ($method === 'messages.getHistory') {
                    return $this->response;
                }

                throw new RuntimeException('cannedHistoryScope: unexpected method '.$method);
            }
        };
    }
}
