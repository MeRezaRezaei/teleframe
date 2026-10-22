<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher as RealDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * UpdateStored fires its listeners AFTER the surrounding transaction
 * commits — the defect this test exists to close.
 *
 * The demonstrated defect (not an inferred one): `MirrorIngesterFactory`
 * wraps mirror ingest in `$db->transaction(...)`, and `MirrorUpdateIngester`
 * emitted `UpdateStored` from INSIDE that closure. A probe against the real
 * classes with a real Illuminate dispatcher over :memory: SQLite observed
 * `DB::transactionLevel() === 1` from inside the listener, then a later
 * listener throwing rolled `mirror_facts` back to 0 rows while the external
 * publish it had already fired did not un-happen. The stored truth and the
 * announced truth disagreed, and the write was lost. `UpdateStored`'s own
 * docblock already claimed "Fired after an ingested update's root
 * transaction commits" — it violated its own documented contract.
 *
 * The fix is the `ShouldDispatchAfterCommit` marker on the event. These
 * tests pin the three things that make it load-bearing:
 *
 *   1. the listener observes level 0, not level 1;
 *   2. a THROWING listener cannot roll back the row that already committed;
 *   3. WITHOUT the marker the listener DOES observe level 1 — the negative
 *      control. A gate never seen to go red is not known to work: an
 *      assertion of "deferred" alone passes just as happily against an event
 *      class that defers nothing, because a listener with no listeners
 *      around it would trivially never run early.
 */
final class UpdateStoredDefersListenersUntilCommitTest extends TestbenchTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('update_stored_probe', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('account_id');
            $table->string('ctor');
        });
    }

    /**
     * A REAL Illuminate dispatcher wired to the app's REAL transaction
     * manager, exactly as `EventServiceProvider` wires the app's own —
     * without that resolver the `ShouldDispatchAfterCommit` branch is never
     * reached and this whole file would pass vacuously.
     */
    private function realDispatcher(): RealDispatcher
    {
        $app = $this->app;

        $events = new RealDispatcher($app);
        $events->setTransactionManagerResolver(static fn (): ?object => $app->bound('db.transactions')
            ? $app->make('db.transactions')
            : null);

        return $events;
    }

    private function seedRow(int $accountId): Model
    {
        $id = DB::table('update_stored_probe')->insertGetId([
            'account_id' => $accountId,
            'ctor' => 'messages',
        ]);

        return ProbeUpdate::query()->findOrFail($id);
    }

    public function test_listener_runs_after_the_transaction_commits_and_observes_level_zero(): void
    {
        $events = $this->realDispatcher();

        $levels = [];
        $events->listen(UpdateStored::class, function () use (&$levels): void {
            $levels[] = DB::transactionLevel();
        });

        $model = $this->seedRow(4242);

        DB::transaction(function () use ($events, $model, &$levels): void {
            self::assertSame(1, DB::transactionLevel(), 'precondition: the dispatch happens INSIDE a transaction');

            $events->dispatch(new UpdateStored($model, 4242));

            self::assertSame([], $levels, 'no listener may run before the transaction commits');
        });

        self::assertSame([0], $levels, 'the listener runs post-commit, at transaction level 0');
        self::assertSame(1, DB::table('update_stored_probe')->count(), 'the row the listener was handed is committed');
    }

    public function test_throwing_listener_cannot_roll_back_the_committed_row(): void
    {
        $events = $this->realDispatcher();

        $levels = [];
        $events->listen(UpdateStored::class, function () use (&$levels): void {
            $levels[] = DB::transactionLevel();
        });
        $events->listen(UpdateStored::class, function (): void {
            throw new \RuntimeException('listener blew up after the fact was stored');
        });

        $model = $this->seedRow(4343);

        // The throw is EXPECTED to escape here, and that is the point of
        // asserting it rather than hiding it. Post-commit callbacks are run
        // by DatabaseTransactionsManager::commit(), i.e. INSIDE the
        // DB::transaction() call, so a throwing listener surfaces from the
        // transaction wrapper even though it can no longer undo the row. What
        // changed is the blast radius: before the fix this throw came from
        // the dispatch itself and rolled the write back; now the truth is
        // already durable and the failure is only a redelivery risk.
        $escaped = null;

        try {
            DB::transaction(fn () => $events->dispatch(new UpdateStored($model, 4343)));
        } catch (\RuntimeException $e) {
            $escaped = $e->getMessage();
        }

        self::assertSame(
            1,
            DB::table('update_stored_probe')->count(),
            'a listener that throws AFTER commit cannot undo the stored truth',
        );
        self::assertSame([0], $levels, 'the observing listener still ran post-commit, at level 0');
        self::assertSame(
            'listener blew up after the fact was stored',
            $escaped,
            'the throw still surfaces from DB::transaction(), but no longer rolls anything back',
        );
    }

    /**
     * THE NEGATIVE CONTROL. The same dispatcher, the same transaction, the
     * same listener — on an event class that does NOT carry the marker.
     *
     * This is the assertion that makes the other two mean something. Without
     * it, "the listener observed level 0" could be satisfied by a listener
     * that simply never ran early for some unrelated reason. Here the
     * listener demonstrably runs INSIDE the transaction, which is precisely
     * the behaviour the marker removes — and it is why the defect could ship
     * behind a docblock that promised the opposite.
     */
    public function test_without_the_marker_the_same_listener_observes_level_one(): void
    {
        $events = $this->realDispatcher();

        $levels = [];
        $events->listen(UnmarkedUpdate::class, function () use (&$levels): void {
            $levels[] = DB::transactionLevel();
        });

        $model = $this->seedRow(4444);

        DB::transaction(fn () => $events->dispatch(new UnmarkedUpdate($model, 4444)));

        self::assertSame(
            [1],
            $levels,
            'control: without ShouldDispatchAfterCommit the listener runs inside the transaction — the exact defect',
        );
        self::assertFalse(
            (new \ReflectionClass(UpdateStored::class))->implementsInterface(
                ShouldDispatchAfterCommit::class,
            ) === false,
            'control is only meaningful while UpdateStored carries the marker',
        );
    }
}

/**
 * The event model the probe carries. A dedicated table keeps this test off
 * the mirror dial, which other lanes are actively changing.
 */
final class ProbeUpdate extends Model
{
    protected $table = 'update_stored_probe';

    protected $guarded = [];
}

/**
 * The negative control's event: byte-identical to `UpdateStored` except for
 * the missing `implements ShouldDispatchAfterCommit`. Anything else — a
 * changed listener, a different dispatcher, a weaker transaction — would let
 * the control pass for the wrong reason.
 */
final class UnmarkedUpdate
{
    public function __construct(
        public readonly Model $model,
        public readonly int $accountId,
    ) {}
}
