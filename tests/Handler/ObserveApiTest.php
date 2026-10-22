<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;

/**
 * Cycle-4 observe/react surface: ``observe()`` is an onMessage-style handler
 * carrying an account scope, flowing the REAL pipeline; ``ignore()`` writes a
 * store_only routing rule when the host seam (DB + event dispatcher) exists
 * and is a documented no-op otherwise.
 */
final class ObserveApiTest extends IngestTestCase
{
    /**
     * The tenants this fixture writes under.
     *
     * MEASURED, not assumed: extracted from the account ids this file
     * actually inserts. It was undeclared, and with foreign-key enforcement
     * on (19 constraints) every row was refused at the DATABASE rather than
     * at the code under test — the test failed on its own fixture.
     *
     * Declared HERE rather than by relaxing seedIngestTenant(): that helper
     * contracts that each test declares the tenants it means to use, so a
     * test writing under an unplanted id fails LOUDLY. These were failing
     * loudly, which is the mechanism working. A blanket auto-seed would also
     * make the orphan-refusal tests unable to plant an orphan.
     */
    protected function ingestTenantIds(): array
    {
        return [8, 7, 9, 10];
    }

    private const ACCOUNT = 7;

    private Teleframe $teleframe;

    protected function setUp(): void
    {
        parent::setUp();
        HandlerRegistry::reset();
        $this->teleframe = $this->app->make(Teleframe::class);
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();
        parent::tearDown();
    }

    public function test_observe_scoped_to_account_fires_only_for_that_account(): void
    {
        $seen = [];
        $this->teleframe->observe(self::ACCOUNT, function (Update $u) use (&$seen): void {
            $seen[] = $u->accountId;
        });

        $results = $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::ACCOUNT, 'ts' => 100],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 8, 'ts' => 101],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::ACCOUNT, 'ts' => 102],
        ]);

        self::assertSame([self::ACCOUNT, self::ACCOUNT], $seen);
        self::assertCount(3, $results);
        self::assertNull($results[1], 'the foreign-account frame is skipped, not acted on');
    }

    public function test_observe_wildcard_fires_for_every_frame(): void
    {
        $seen = [];
        $this->teleframe->observe('*', function (Update $u) use (&$seen): void {
            $seen[] = $u->accountId;
        });

        $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 7, 'ts' => 200],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 8, 'ts' => 201],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 9, 'ts' => 202],
        ]);

        self::assertSame([7, 8, 9], $seen);
    }

    public function test_observe_accepts_a_numeric_string_scope_like_the_int_form(): void
    {
        $seen = [];
        $this->teleframe->observe('7', function (Update $u) use (&$seen): void {
            $seen[] = $u->accountId;
        });

        $results = $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 7, 'ts' => 300],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 8, 'ts' => 301],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 7, 'ts' => 302],
        ]);

        self::assertSame([7, 7], $seen, 'numeric-string scope must behave exactly like the int scope');
        self::assertNull($results[1], 'the foreign-account frame is skipped');
    }

    public function test_observe_numeric_string_and_wildcard_scope_agree_with_the_int_forms(): void
    {
        $seen = [];
        $this->teleframe->observe('7', function (Update $u) use (&$seen): void {
            $seen[] = 'n';
        });
        $this->teleframe->observe(7, function (Update $u) use (&$seen): void {
            $seen[] = 'i';
        });

        $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 7, 'ts' => 400],
        ]);

        self::assertCount(1, $seen, 'first-match-wins: int and numeric-string are the SAME scope — a single registration fires');
    }

    public function test_observe_handles_string_callables_resolved_from_container(): void
    {
        $this->app->instance(RecordObserver::class, new RecordObserver);

        $this->teleframe->observe(self::ACCOUNT, RecordObserver::class);

        $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::ACCOUNT],
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => 10],
        ]);

        self::assertSame([self::ACCOUNT], app(RecordObserver::class)->accounts);
    }

    public function test_observe_returns_self_for_fluent_chaining(): void
    {
        $return = $this->teleframe->observe(self::ACCOUNT, fn () => null);

        self::assertSame($this->teleframe, $return);
    }

    public function test_ignore_writes_a_store_only_routing_rule_when_the_db_is_available(): void
    {
        $return = $this->teleframe->ignore(self::ACCOUNT, 2, 99);
        self::assertSame($this->teleframe, $return);

        $row = DB::table('tg_update_routing')
            ->where('account_id', self::ACCOUNT)
            ->where('peer_type', 2)
            ->where('peer_id', 99)
            ->first();

        self::assertNotNull($row, 'the store_only rule must be committed to tg_update_routing');
        self::assertSame('store_only', $row->mode);
        self::assertSame(0, (int) $row->priority);
    }

    public function test_ignore_without_peer_defaults_to_the_account_wide_catch_all(): void
    {
        $this->teleframe->ignore(self::ACCOUNT);

        self::assertSame(
            'store_only',
            DB::table('tg_update_routing')
                ->where('account_id', self::ACCOUNT)
                ->where('peer_type', 0)
                ->where('peer_id', 0)
                ->value('mode'),
        );
    }

    public function test_ignore_is_a_noop_without_the_container_seam(): void
    {
        $teleframe = new Teleframe(new ArrayContainer);

        $return = $teleframe->ignore(self::ACCOUNT, 2, 99);

        self::assertSame($teleframe, $return);
        self::assertNull(
            DB::table('tg_update_routing')
                ->where('account_id', self::ACCOUNT)
                ->where('peer_type', 2)
                ->where('peer_id', 99)
                ->first(),
            'a container without the DB/event seam must not write anything',
        );
    }
}

final class RecordObserver
{
    /** @var list<int> */
    public array $accounts = [];

    public function __invoke(Update $update): void
    {
        $this->accounts[] = $update->accountId;
    }
}
