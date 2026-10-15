<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Tests;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface;
use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramGapDetected;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use PHPUnit\Framework\TestCase;

/**
 * D-041: the producer mints its own frame identity, and this file is the gate
 * on WHAT it mints and HOW.
 *
 * ## WHY THE PRODUCER MINTS ONE AT ALL
 *
 * `UpdateIngestor::frameKey()` derives a seq-less update's negative `(seq,
 * position)` from a frame identity, and until the S1 inversion that identity
 * was the Redis stream entry id (`IngestConsumer::handleEntry()` threaded
 * `$entryId` down for exactly that). Under the inversion the entry is
 * published AFTER the row commits, so at the moment the key must be derived
 * no entry id exists. The producer therefore mints
 * `producer:<accountId>:<fromPts>:<ordinal>` itself.
 *
 * ## WHAT THIS FILE PROVES, AND WHAT IT CANNOT
 *
 * It proves the frame is deterministic, correctly attributed, correctly
 * COUNTED, and scoped — the properties `tests/Ingest/ProducerFrameIdempotencyTest`
 * then spends against a real database. It deliberately asserts the exact
 * frame strings: "the ordinal is not the container index" is invisible in a
 * row count, because both schemes produce N rows for a window whose updates
 * sit in ONE loop. It only bites when the ordinals are pinned.
 *
 * @internal
 */
final class ProducerFrameTest extends TestCase
{
    /** The TELEGRAM user id carried by the live session. NOT a tenancy id. */
    private const TELEGRAM_USER_ID = 501;

    /** The TENANCY account id the daemon configures — what frames carry. */
    private const ACCOUNT = 4242;

    /** The cursor the fake window is requested from. */
    private const FROM_PTS = 8;

    /**
     * A sink that records what it saw, INCLUDING the frame in scope at the
     * moment of the call.
     *
     * `$poller` is assigned after construction because the poller takes the
     * sink — and this is exactly the wiring S1 step 2 will use, since the
     * frame reaches the ingest from inside `handle()`.
     *
     * @param  list<array{update: array<string, mixed>, frame: string|null}>  $storage
     * @return UpdateSinkInterface&object{poller: UpdatePollerService|null}
     */
    private function framingSink(array &$storage): UpdateSinkInterface
    {
        return new class($storage) implements UpdateSinkInterface
        {
            public ?UpdatePollerService $poller = null;

            /**
             * @param  list<array{update: array<string, mixed>, frame: string|null}>  $storage
             */
            public function __construct(private array &$storage) {}

            public function handle(array $update, ?string $source = null): bool
            {
                $this->storage[] = ['update' => $update, 'frame' => $this->poller?->getProducerFrame()];

                return true;
            }
        };
    }

    /**
     * A sink that always throws, so the scoping of the frame can be measured
     * across an exit that is not a return.
     */
    private function throwingSink(): UpdateSinkInterface
    {
        return new class implements UpdateSinkInterface
        {
            public function handle(array $update, ?string $source = null): bool
            {
                throw new \RuntimeException('sink refused');
            }
        };
    }

    /**
     * A user scope replaying canned `updates.getDifference` responses, with
     * the TELEGRAM user id on its session. The halt callback stops the poller
     * once the sequence is exhausted.
     *
     * @param  list<array<string, mixed>>  $sequence
     * @param  list<array{method: string, params: array<string, mixed>}>  $calls
     */
    private function fakeScope(array $sequence, array &$calls, ?Closure $halt): UserAccountScope
    {
        return new class($sequence, $halt, $calls) extends UserAccountScope
        {
            public function __construct(
                private readonly array $sequence,
                private readonly ?Closure $halt,
                private array &$calls
            ) {
                parent::__construct(
                    new MTProtoClient(apiId: 1, apiHash: 'hash', live: false),
                    new SessionData(dcId: 2, authKey: random_bytes(256), userId: ProducerFrameTest::telegramUserId())
                );
            }

            public function call(string $method, array $params = []): array
            {
                if (count($this->calls) >= count($this->sequence)) {
                    if ($this->halt !== null) {
                        ($this->halt)();
                    }

                    throw new \RuntimeException('halt polling');
                }

                $this->calls[] = ['method' => $method, 'params' => $params];

                return $this->sequence[count($this->calls) - 1];
            }
        };
    }

    public static function telegramUserId(): int
    {
        return self::TELEGRAM_USER_ID;
    }

    /**
     * Run one polled window and return what the sink saw.
     *
     * @param  list<array<string, mixed>>  $sequence
     * @param  (Closure(array<string, mixed>): bool)|null  $filter
     * @return list<array{update: array<string, mixed>, frame: string|null}>
     */
    private function pollWindow(array $sequence, ?Closure $filter = null, int $fromPts = self::FROM_PTS): array
    {
        $captured = [];
        $calls = [];
        $poller = (new UpdatePollerService($this->framingSink($captured)))
            ->setProducerAccountId(self::ACCOUNT);

        if ($filter !== null) {
            $poller->filter($filter);
        }

        $poller->setSequenceState(['pts' => $fromPts, 'date' => 11, 'qts' => 1, 'seq' => 0]);

        $sink = $poller->getSink();
        $sink->poller = $poller;

        $poller->pollUser($this->fakeScope($sequence, $calls, fn () => $poller->stop()));

        return $captured;
    }

    /**
     * A difference carrying two `other_updates`, one encrypted message and two
     * messages — every kind `streamUserPayload()` unwraps.
     *
     * @return array<string, mixed>
     */
    private function window(): array
    {
        return [
            '_' => 'updates.difference',
            'state' => ['_' => 'updates.state', 'pts' => 40, 'date' => 20, 'qts' => 3, 'seq' => 1],
            'other_updates' => [
                ['_' => 'updateReadHistoryInbox', 'pts' => 9, 'pts_count' => 1],
                ['_' => 'updateReadHistoryInbox', 'pts' => 9, 'pts_count' => 2],
            ],
            'new_encrypted_messages' => [['_' => 'encryptedMessage', 'random_id' => 77, 'chat_id' => 5]],
            'new_messages' => [
                ['id' => 7, 'message' => 'hi', 'peer_id' => ['_' => 'peerUser', 'user_id' => 2]],
                ['id' => 8, 'message' => 'yo', 'peer_id' => ['_' => 'peerUser', 'user_id' => 2]],
            ],
        ];
    }

    /**
     * THE FORMAT, and the account component's identity.
     *
     * The frame carries the TENANCY account id — the one injected from the
     * daemon's account config — and never the Telegram user id the poller can
     * see on its own scope. The two differ here on purpose (`4242` vs `501`),
     * because that confusion is the mistake this assertion exists to kill: the
     * row's `account_id` is the tenancy id, and a frame stamped with the
     * Telegram id would be attributed to the wrong account by anything reading
     * the frame as an address.
     */
    public function test_the_frame_carries_the_tenancy_account_id_not_the_telegram_user_id(): void
    {
        $captured = $this->pollWindow([$this->window()]);

        self::assertSame(
            [
                'producer:4242:8:0',
                'producer:4242:8:1',
                'producer:4242:8:2',
                'producer:4242:8:3',
                'producer:4242:8:4',
            ],
            $this->frames($captured),
        );

        foreach ($this->frames($captured) as $frame) {
            self::assertStringNotContainsString(
                (string) self::TELEGRAM_USER_ID,
                $frame,
                'a producer frame must not be stamped with the Telegram user id',
            );
        }
    }

    /**
     * THE ORDINAL, counted across the UNWRAPPED updates of the window.
     *
     * `streamUserPayload()` delivers `other_updates`, then the wrapped
     * `new_encrypted_messages`, then the wrapped `new_messages`. The ordinals
     * below are 0,1,2,3,4 across that whole sequence — one counter, not one
     * per loop. A per-loop counter stamps the encrypted message and the first
     * message `…:0` as well, so both share a frame with `other_updates[0]`
     * and three updates fold onto one row. That is invisible in a row count,
     * which is why the strings are pinned here.
     */
    public function test_the_ordinal_counts_unwrapped_updates_across_all_three_containers(): void
    {
        $captured = $this->pollWindow([$this->window()]);

        // MadelineProto delivery order survives: two raw other_updates, then
        // the wrapped encrypted message, then the two wrapped messages.
        self::assertSame(
            ['updateReadHistoryInbox', 'updateReadHistoryInbox', 'updateNewEncryptedMessage', 'updateNewMessage', 'updateNewMessage'],
            array_map(static fn (array $c): string => (string) ($c['update']['_'] ?? ''), $captured),
        );

        self::assertSame(
            ['producer:4242:8:0', 'producer:4242:8:1', 'producer:4242:8:2', 'producer:4242:8:3', 'producer:4242:8:4'],
            $this->frames($captured),
        );

        // No two updates share a frame — the property the row count rests on.
        self::assertSame($this->frames($captured), array_values(array_unique($this->frames($captured))));
    }

    /**
     * DETERMINISM ACROSS A CRASH-RESTART.
     *
     * Two independent pollers — separate instances, as two processes would be
     * — seeded to the same persisted cursor and shown the same window, which is
     * what a process that died mid-window and came back sees. Every frame
     * re-derives identically, so the second delivery UPSERTS onto the first
     * delivery's rows instead of appending duplicates. This is the property
     * that licenses the producer to mint its own identity: the cursor is
     * persisted only after `run()` ends, so the re-fetch is always from the
     * pts the window was REQUESTED at.
     */
    public function test_a_window_refetched_after_a_crash_mints_the_same_frames(): void
    {
        $beforeCrash = $this->pollWindow([$this->window()]);
        $afterRestart = $this->pollWindow([$this->window()]);

        self::assertSame(
            $this->frames($beforeCrash),
            $this->frames($afterRestart),
            'the re-fetch of one window must re-derive the same frames, or the re-delivery appends duplicates',
        );
        self::assertSame($this->payloads($beforeCrash), $this->payloads($afterRestart));
    }

    /**
     * A DIFFERENT window — a later pts — mints different frames from the same
     * ordinals. Ordinals alone are per window, so the requested pts is what
     * keeps two windows apart; this is the case that would fold if the frame
     * were the ordinal alone.
     */
    public function test_a_later_window_mints_different_frames_from_the_same_ordinals(): void
    {
        $captured = $this->pollWindow([$this->window()], fromPts: 999);

        self::assertSame(
            ['producer:4242:999:0', 'producer:4242:999:1', 'producer:4242:999:2', 'producer:4242:999:3', 'producer:4242:999:4'],
            $this->frames($captured),
        );
    }

    /**
     * A rejected update STILL CONSUMES ITS ORDINAL.
     *
     * The counter advances at emission, not on a successful sink call, so a
     * filter that drops the second update shifts nothing: the third update
     * keeps ordinal 2 and a re-fetch under a changed filter still re-derives
     * the same frames for the updates that were emitted. Counting only
     * accepted updates would renumber everything after each rejection, so the
     * same update would land on a different frame on the re-fetch.
     */
    public function test_a_rejected_update_still_consumes_its_ordinal(): void
    {
        $captured = $this->pollWindow(
            [$this->window()],
            filter: static fn (array $update): bool => ($update['pts'] ?? 0) !== 9,
        );

        self::assertCount(3, $captured, 'both pts-bearing other_updates are filtered out');
        self::assertSame(
            ['producer:4242:8:2', 'producer:4242:8:3', 'producer:4242:8:4'],
            $this->frames($captured),
            'a rejected update consumes its ordinal, so later frames do not shift',
        );
    }

    /**
     * THE FRAME IS SCOPED, NOT STICKY.
     *
     * It is readable only while the sink is handling that update. A frameless
     * caller — the Bot API loop, a host calling `processUpdate()` directly —
     * must see null, never the previous MTProto window's frame; and a throw
     * out of the sink must not leak one into the next update.
     */
    public function test_the_frame_is_in_scope_only_while_the_sink_handles_that_update(): void
    {
        $captured = [];
        $sink = $this->framingSink($captured);
        $poller = (new UpdatePollerService($sink))->setProducerAccountId(self::ACCOUNT);
        $sink->poller = $poller;

        // A direct, FRAMELESS call — the Bot API loop, or any host calling
        // processUpdate() itself. The sink must see null, never the previous
        // MTProto window's frame.
        $poller->processUpdate(['_' => 'updateReadHistoryInbox'], (string) self::TELEGRAM_USER_ID);

        // The same update WITH a frame: readable only for that one call.
        $poller->processUpdate(
            ['_' => 'updateReadHistoryInbox'],
            (string) self::TELEGRAM_USER_ID,
            null,
            $poller->mintProducerFrame(self::FROM_PTS, 0),
        );

        self::assertSame(
            [null, 'producer:4242:8:0'],
            array_column($captured, 'frame'),
            'a frameless update must reach the sink with NO frame, and a framed one with exactly its own',
        );
        self::assertNull($poller->getProducerFrame(), 'no frame is in scope between updates');

        // A throw out of the sink is the exit a `finally` has to cover: the
        // frame must not survive into the next update.
        $poller->setSink($this->throwingSink());

        try {
            $poller->processUpdate(
                ['_' => 'updateReadHistoryInbox'],
                (string) self::TELEGRAM_USER_ID,
                null,
                $poller->mintProducerFrame(self::FROM_PTS, 1),
            );
            self::fail('the throwing sink must propagate');
        } catch (\RuntimeException $e) {
            self::assertSame('sink refused', $e->getMessage());
        }

        self::assertNull(
            $poller->getProducerFrame(),
            'a throw out of the sink must not leak its frame into the next update',
        );
    }

    /**
     * A poller nobody injected a tenancy account into still mints well-formed
     * frames, stamped `0`. It is a host that polls without the daemon; the
     * frame stays readable and `frameKey()` still separates tenants by hashing
     * the ingestor's own account id alongside it.
     */
    public function test_a_poller_without_an_injected_account_mints_account_zero(): void
    {
        self::assertSame('producer:0:8:0', (new UpdatePollerService)->mintProducerFrame(8, 0));
        self::assertNull(
            (new UpdatePollerService)->getProducerFrame(),
            'a frame is in scope only while an update is being handled, never by default',
        );
    }

    /**
     * A slice advances the window, so the NEXT slice's frames are minted from
     * the pts it was requested at — a different `fromPts`, hence a different
     * frame for the same ordinal. This is the path that proves `fromPts` is
     * the requested pts and not the adopted one.
     */
    public function test_a_slice_mints_its_frames_from_the_pts_it_was_requested_at(): void
    {
        $captured = [];

        $this->withEventDispatcher(function () use (&$captured): void {
            $captured = $this->pollWindow([
                [
                    '_' => 'updates.differenceSlice',
                    'intermediate_state' => ['_' => 'updates.state', 'pts' => 30, 'date' => 20, 'qts' => 3, 'seq' => 1],
                    'other_updates' => [['_' => 'updateReadHistoryInbox', 'pts' => 9, 'pts_count' => 1]],
                ],
                [
                    '_' => 'updates.differenceSlice',
                    'intermediate_state' => ['_' => 'updates.state', 'pts' => 40, 'date' => 21, 'qts' => 3, 'seq' => 2],
                    'other_updates' => [['_' => 'updateReadHistoryInbox', 'pts' => 31, 'pts_count' => 1]],
                ],
            ]);
        });

        self::assertSame(
            ['producer:4242:8:0', 'producer:4242:30:0'],
            $this->frames($captured),
            'the second slice is framed from the pts the FIRST slice advanced the cursor to',
        );
    }

    /**
     * @param  list<array{update: array<string, mixed>, frame: string|null}>  $captured
     * @return list<string>
     */
    private function frames(array $captured): array
    {
        return array_map(static fn (array $c): string => (string) $c['frame'], $captured);
    }

    /**
     * @param  list<array{update: array<string, mixed>, frame: string|null}>  $captured
     * @return list<string>
     */
    private function payloads(array $captured): array
    {
        return array_map(static fn (array $c): string => (string) json_encode($c['update']), $captured);
    }

    /**
     * Wire the Laravel Event facade to a real in-memory dispatcher (the slice
     * path dispatches TelegramGapDetected) and restore the previous
     * application afterwards.
     *
     * @param  Closure(): mixed  $body
     */
    private function withEventDispatcher(Closure $body): mixed
    {
        $previousApp = Event::getFacadeApplication();

        $app = new Container;
        $dispatcher = new EventsDispatcher($app);
        Event::setFacadeApplication($app);
        Event::swap($dispatcher);
        $dispatcher->listen(TelegramGapDetected::class, static fn (): null => null);

        try {
            return $body();
        } finally {
            Event::setFacadeApplication($previousApp);
            Event::clearResolvedInstances();
        }
    }
}
