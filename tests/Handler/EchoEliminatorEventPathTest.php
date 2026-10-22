<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * The route-level loop guard must fire on BOTH intake routes.
 *
 * DEFECT (2026-09-30 audit). `EchoEliminator` read its evidence only from
 * `$update->array` (`random_id` / `msg_id`). The BUS path carries those in the
 * transport payload, so the guard fired there. The EVENT path
 * (`Update::fromMirror`, the SHIPPED default — `UpdateStoredHandler` is
 * registered by `TeleframeServiceProvider:965`) built a minimal identity row
 * of `['_'] => ctor` only, so BOTH ids were `''`, `lookup()` returned null at
 * :78-80, and the middleware fell straight through to `$next($update)`
 * unconditionally on EVERY event frame.
 *
 * Why that is not cosmetic: ingest-side `SelfOriginatedClassifier` catches own
 * sends via the `out` flag and defaults them to `store_only`, but its own
 * documented escape hatch (`SelfOriginatedClassifier.php:66-70`, "since some
 * times we need to act on something special") lets a peer rule `act_on` an own
 * send through AS AN INPUT. That own send is then an ordinary input event, and
 * the one route-level loop guard was blind to it — a feedback loop back into
 * the same handlers.
 *
 * The asymmetry that made it a defect and not a design: `ReplayDedup:38-53`
 * and `UpdateDispatcher::forwardKey()` BOTH carry an explicit, dated comment
 * explaining their event-path skip. `EchoEliminator` carried NONE — its guard
 * was skipped on one path by ACCIDENT, and the reason was written down
 * nowhere. This test is that missing reasoning, executable.
 *
 * Both directions are asserted, because "make it fire" would equally pass a
 * change that eliminates EVERY event frame — over-elimination (a starved
 * handler, the same class of harm as the 1ee8738d mass-drop) is as bad as the
 * defect, so a non-echo and an id-less frame must both still reach the chain.
 */
final class EchoEliminatorEventPathTest extends TestCase
{
    private const ACCOUNT = 7;

    private ArrayCache $cache;

    private HandlerRegistry $registry;

    private EchoEliminator $eliminator;

    private int $fired = 0;

    protected function setUp(): void
    {
        $this->cache = new ArrayCache;
        $this->registry = new HandlerRegistry;
        $this->registry->on('updateNewMessage', function (): void {
            $this->fired++;
        }, 0, false);
        $this->registry->on('updateOwned', function (): void {
            $this->fired++;
        }, 0, true);
        $this->eliminator = new EchoEliminator($this->cache, new HandlerMatcher($this->registry));
        $this->fired = 0;
    }

    /**
     * The send path the class already documents as its writer: the facade's
     * `Teleframe::send()`, which is also the normalizer AppRuntime::rememberSend
     * feeds (`id` is folded into `msg_id`, AppRuntime:404-411).
     */
    private function recordSend(int $accountId, array $send): void
    {
        $container = new ArrayContainer([
            CacheInterface::class => $this->cache,
            HandlerRegistry::class => $this->registry,
        ]);

        (new Teleframe($container))->send($accountId, $send);
    }

    /**
     * A curated message root as the NF5 mirror writes it. `id` IS the Telegram
     * message id, i.e. the msg_id the send registry is keyed on. No DB: the
     * model is only ever read through its already-hydrated attributes.
     */
    private function storedMessage(int $accountId, int $id, string $constructor = 'updateNewMessage'): TfMessage
    {
        $model = new TfMessage;
        $model->setAttribute('account_id', $accountId);
        $model->setAttribute('id', $id);
        $model->setAttribute('out', true);
        $model->setAttribute('constructor', $constructor);

        return $model;
    }

    /** The standalone update root — no per-message id on it at all. */
    private function storedUpdate(int $accountId, string $constructor = 'updateUserStatus'): TfUpdate
    {
        $model = new TfUpdate;
        $model->setAttribute('account_id', $accountId);
        $model->setAttribute('seq', 0);
        $model->setAttribute('position', 0);
        $model->setAttribute('constructor', $constructor);

        return $model;
    }

    private function eliminate(Update $update): mixed
    {
        $eliminator = $this->eliminator;

        return $eliminator($update, function (Update $u): string {
            $this->fired++;

            return 'ran';
        });
    }

    // ---------------------------------------------------------------------
    // 1. Bus path — the regression half. Already green before the fix.
    // ---------------------------------------------------------------------

    public function test_bus_frame_of_a_recorded_send_is_eliminated(): void
    {
        $this->recordSend(self::ACCOUNT, ['random_id' => 'r-1', 'msg_id' => '42', 'sent_at' => 1]);

        $result = $this->eliminate(Update::fromBus(
            ['_' => 'updateNewMessage', 'random_id' => 'r-1', 'msg_id' => '42'],
            self::ACCOUNT,
            100,
        ));

        self::assertSame(0, $this->fired, 'the bus echo must not reach the handler');
        self::assertNull($result, 'elimination returns null, not the handler result');
    }

    // ---------------------------------------------------------------------
    // 2. Event path — THE DEFECT. Red before the fix.
    // ---------------------------------------------------------------------

    public function test_event_frame_of_the_same_recorded_send_is_eliminated(): void
    {
        $this->recordSend(self::ACCOUNT, ['random_id' => 'r-1', 'msg_id' => '42', 'sent_at' => 1]);

        $this->eliminate(Update::fromMirror($this->storedMessage(self::ACCOUNT, 42), self::ACCOUNT));

        self::assertSame(0, $this->fired, 'the event echo must not reach the handler either');
    }

    public function test_event_frame_is_eliminated_by_a_bare_msg_id_alone(): void
    {
        // The send that only the server ever saw an id for — no random_id.
        $this->recordSend(self::ACCOUNT, ['msg_id' => '42', 'sent_at' => 1]);

        $this->eliminate(Update::fromMirror($this->storedMessage(self::ACCOUNT, 42), self::ACCOUNT));

        self::assertSame(0, $this->fired);
    }

    public function test_event_frame_on_own_handler_still_runs_with_the_flag_set(): void
    {
        $this->recordSend(self::ACCOUNT, ['msg_id' => '42', 'sent_at' => 1]);

        $seen = null;
        $eliminator = $this->eliminator;
        $result = $eliminator(
            Update::fromMirror($this->storedMessage(self::ACCOUNT, 42, 'updateOwned'), self::ACCOUNT),
            function (Update $u) use (&$seen): string {
                $seen = $u;

                return 'ran';
            },
        );

        self::assertSame('ran', $result);
        self::assertNotNull($seen);
        self::assertTrue($seen->selfOriginated, 'the route-level opt-in must still see the verdict');
        self::assertSame(0, $this->fired, 'the raw counter handler is the non-opt-in one');
    }

    // ---------------------------------------------------------------------
    // 3. No over-elimination. Over-eliminating is as bad as the defect.
    // ---------------------------------------------------------------------

    public function test_non_echo_event_frame_with_a_different_id_reaches_the_handler(): void
    {
        $this->recordSend(self::ACCOUNT, ['msg_id' => '42', 'sent_at' => 1]);

        $result = $this->eliminate(Update::fromMirror($this->storedMessage(self::ACCOUNT, 99), self::ACCOUNT));

        self::assertSame(1, $this->fired);
        self::assertSame('ran', $result);
    }

    public function test_event_frame_for_another_account_is_not_eliminated(): void
    {
        $this->recordSend(self::ACCOUNT, ['msg_id' => '42', 'sent_at' => 1]);

        $this->eliminate(Update::fromMirror($this->storedMessage(self::ACCOUNT + 1, 42), self::ACCOUNT + 1));

        self::assertSame(1, $this->fired, 'the registry is account-scoped and must stay so');
    }

    public function test_event_frame_carrying_no_ids_at_all_reaches_the_handler(): void
    {
        $this->recordSend(self::ACCOUNT, ['msg_id' => '42', 'sent_at' => 1]);

        $result = $this->eliminate(Update::fromMirror($this->storedUpdate(self::ACCOUNT), self::ACCOUNT));

        self::assertSame(1, $this->fired, 'a row with no identity must pass, not be dropped');
        self::assertSame('ran', $result);
    }

    public function test_event_frame_is_not_eliminated_when_nothing_was_ever_sent(): void
    {
        $this->eliminate(Update::fromMirror($this->storedMessage(self::ACCOUNT, 42), self::ACCOUNT));

        self::assertSame(1, $this->fired);
    }

    // ---------------------------------------------------------------------
    // 4. The identity contract itself must survive the fix.
    // ---------------------------------------------------------------------

    public function test_the_constructor_key_is_untouched_by_the_identity_row_change(): void
    {
        $update = Update::fromMirror($this->storedMessage(self::ACCOUNT, 42), self::ACCOUNT);

        self::assertSame('updateNewMessage', $update->constructor());
        self::assertSame('event', $update->source);
    }

    public function test_a_legacy_row_with_no_constructor_still_degrades_to_the_catch_all(): void
    {
        $legacy = new TfMessage;
        $legacy->setAttribute('account_id', self::ACCOUNT);
        $legacy->setAttribute('id', 42);

        $update = Update::fromMirror($legacy, self::ACCOUNT);

        self::assertSame('', $update->constructor());
    }

    public function test_the_identity_row_still_carries_no_raw_payload(): void
    {
        $update = Update::fromMirror($this->storedMessage(self::ACCOUNT, 42), self::ACCOUNT);

        self::assertArrayNotHasKey('out', $update->array, 'the mirror is the payload, not the array');
        self::assertArrayNotHasKey('account_id', $update->array);
    }
}
