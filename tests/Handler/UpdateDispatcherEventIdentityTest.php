<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;

/**
 * The event-path forward guard must key on a row identity that actually
 * distinguishes one update from the next.
 *
 * Two prior defects, one shape, and the second is invisible to the first's
 * tests:
 *
 *  1. `1ee8738d` — the guard keyed on `Update::updateId()`, which over the
 *     event path's constructor-only `['_'] => ctor` row is a constant per
 *     (account, constructor). It was moved onto the mirrored model.
 *  2. THIS FILE — the mirrored model does not always HAVE a per-message
 *     identity. `TfUpdate::$primaryKey = 'account_id'` is a documented
 *     stand-in (TfUpdate.php:32 and its docblock at :14-17), and asking for
 *     the real composite key is necessary but NOT sufficient: the standalone
 *     write path calls `UpdateIngestor::writeUpdate($payload, $accountId,
 *     $ctor)` with the defaults `$seq = 0, $position = 0`
 *     (UpdateIngestor.php:532, called at :319), so EVERY standalone update of
 *     a constructor lands on the SAME `(account_id, 0, 0)` slot. The facts
 *     that differ (user_id, status) live in CHILD tables that
 *     `getAttributes()` never sees.
 *
 * So the row genuinely has no identity, and the honest answer is to decline to
 * dedupe rather than to invent a constant key. Over-forwarding is recoverable;
 * a constant key starves the handler forever, silently, for the life of the
 * process — and `UpdateStoredHandler` is registered by the shipped provider
 * (TeleframeServiceProvider.php:965) over a SINGLETON dispatcher (:146).
 *
 * Both directions are asserted, because "remove the dedup" also passes the
 * first test and is equally wrong.
 */
final class UpdateDispatcherEventIdentityTest extends TestCase
{
    private HandlerRegistry $registry;

    private UpdateDispatcher $dispatcher;

    private int $fired = 0;

    protected function setUp(): void
    {
        $this->registry = new HandlerRegistry;
        $this->dispatcher = new UpdateDispatcher(
            $this->registry,
            new Pipeline,
            new ArrayContainer,
            new ArrayCache,
        );
        $this->fired = 0;
    }

    /**
     * A standalone `TfUpdate` root: the shared `(account_id, 0, 0)` slot.
     * The `status` attribute stands in for a CHILD fact — it is deliberately
     * NOT on the model, exactly as in production, which is the whole point.
     */
    private function standaloneUpdateStatus(int $accountId, string $constructor = 'updateUserStatus'): TfUpdate
    {
        $model = new TfUpdate;
        $model->setAttribute('account_id', $accountId);
        $model->setAttribute('seq', 0);
        $model->setAttribute('position', 0);
        $model->setAttribute('constructor', $constructor);

        return $model;
    }

    /** THE REGRESSION. Two DISTINCT events, one shared slot → both fire. */
    public function test_two_distinct_standalone_updates_both_reach_the_handler(): void
    {
        $this->registry->on('updateUserStatus', function (Update $u): void {
            $this->fired++;
        });

        // Event 1: user 5 online. Event 2: user 9 offline. Distinct events,
        // byte-identical mirrored parents — only the CHILD facts differ, and
        // the dispatcher cannot see those.
        $this->dispatcher->dispatch(Update::fromMirror($this->standaloneUpdateStatus(7), 7));
        $this->dispatcher->dispatch(Update::fromMirror($this->standaloneUpdateStatus(7), 7));

        self::assertSame(
            2,
            $this->fired,
            'two distinct standalone updates must both reach the handler; a key constant per (account, constructor) drops the second in silence',
        );
    }

    /**
     * The same, over a constructor the app actually registers for — a bot that
     * must react to the Nth callback query of the day reacts to one.
     */
    public function test_repeated_standalone_callback_queries_all_reach_the_handler(): void
    {
        $this->registry->on('updateBotCallbackQuery', function (Update $u): void {
            $this->fired++;
        });

        for ($i = 1; $i <= 5; $i++) {
            $this->dispatcher->dispatch(
                Update::fromMirror($this->standaloneUpdateStatus(3, 'updateBotCallbackQuery'), 3),
            );
        }

        self::assertSame(5, $this->fired, 'every standalone callback query reaches the handler, not just the first');
    }

    /** NON-REGRESSION, forward: distinct rows with a real key both fire. */
    public function test_two_distinct_mirrored_messages_both_reach_the_handler(): void
    {
        $this->registry->on('message', function (Update $u): void {
            $this->fired++;
        });

        foreach ([101, 102] as $id) {
            $model = new TfMessage;
            $model->setAttribute('account_id', 7);
            $model->setAttribute('id', $id);
            $model->setAttribute('constructor', 'message');

            $this->dispatcher->dispatch(Update::fromMirror($model, 7));
        }

        self::assertSame(2, $this->fired, 'two distinct mirrored messages must both reach the handler');
    }

    /**
     * NON-REGRESSION, the other direction: the fix must NOT be "remove the
     * dedup". `TfMessage` has a genuine per-row id, so a re-delivery of the
     * SAME row — which the ingestor upserts idempotently — still collapses to
     * one handler run.
     */
    public function test_a_redelivered_mirrored_message_is_still_forwarded_once(): void
    {
        $this->registry->on('message', function (Update $u): void {
            $this->fired++;
        });

        $model = new TfMessage;
        $model->setAttribute('account_id', 7);
        $model->setAttribute('id', 101);
        $model->setAttribute('constructor', 'message');

        $update = Update::fromMirror($model, 7);

        $this->dispatcher->dispatch($update);
        $this->dispatcher->dispatch($update);
        $this->dispatcher->dispatch($update);

        self::assertSame(1, $this->fired, 'a re-delivered row with a real identity is forwarded once only');
    }

    /**
     * A legitimate EDIT of one row still forwards: the attribute hash rides
     * alongside the key, so content that changed is not mistaken for a
     * re-delivery. Guards against "fixing" the mass-drop by keying on the
     * attributes alone.
     */
    public function test_an_edit_of_the_same_message_row_is_still_forwarded(): void
    {
        $this->registry->on('message', function (Update $u): void {
            $this->fired++;
        });

        $first = new TfMessage;
        $first->setAttribute('account_id', 7);
        $first->setAttribute('id', 101);
        $first->setAttribute('constructor', 'message');
        $first->setAttribute('message', 'before');

        $edit = new TfMessage;
        $edit->setAttribute('account_id', 7);
        $edit->setAttribute('id', 101);
        $edit->setAttribute('constructor', 'message');
        $edit->setAttribute('message', 'after');

        $this->dispatcher->dispatch(Update::fromMirror($first, 7));
        $this->dispatcher->dispatch(Update::fromMirror($edit, 7));

        self::assertSame(2, $this->fired, 'an edited row is a different update, not a re-delivery');
    }

    /**
     * The container path DOES thread a real seq/position per member
     * (`UpdateIngestor::writeUpdateContainer`), so those rows keep a genuine
     * identity and their re-delivery still collapses. This is the path
     * 1ee8738d tested; it must keep working.
     */
    public function test_container_members_keep_a_real_identity(): void
    {
        $this->registry->on('updateUserStatus', function (Update $u): void {
            $this->fired++;
        });

        $member = new TfUpdate;
        $member->setAttribute('account_id', 7);
        $member->setAttribute('seq', 5);
        $member->setAttribute('position', 2);
        $member->setAttribute('constructor', 'updateUserStatus');

        $update = Update::fromMirror($member, 7);

        $this->dispatcher->dispatch($update);
        $this->dispatcher->dispatch($update);

        self::assertSame(1, $this->fired, 'a container member at (7, 5, 2) is one row and re-delivers as one');
    }

    /**
     * The premise, asserted rather than assumed: the standalone slot really is
     * the same row for both events, and the container member really is not.
     * Without this, the tests above could pass for the wrong reason.
     */
    public function test_the_standalone_slot_is_shared_and_the_container_slot_is_not(): void
    {
        $one = $this->standaloneUpdateStatus(7);
        $two = $this->standaloneUpdateStatus(7);

        self::assertSame($one->childKey(), $two->childKey(), 'both standalone updates occupy (7, 0, 0)');
        self::assertSame(
            [7, 0, 0],
            array_values($one->childKey()),
            'the standalone key carries no per-message component at all',
        );

        $container = new TfUpdate;
        $container->setAttribute('account_id', 7);
        $container->setAttribute('seq', 5);
        $container->setAttribute('position', 2);
        $container->setAttribute('constructor', 'updateUserStatus');

        self::assertNotSame($one->childKey(), $container->childKey());
        self::assertSame(
            [7, 5, 2],
            array_values($container->childKey()),
            'a container member carries a real per-update key',
        );
        self::assertSame(
            '7-5-2',
            implode('-', array_map('strval', $container->childKey())),
            'and it serializes to a per-member identity',
        );
    }
}
