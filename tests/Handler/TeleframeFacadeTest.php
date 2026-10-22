<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * Facade delegates to the real module singletons (compose, never own) —
 * wire the resolver, run a constructor-routed handler through the REAL
 * pipeline, and let the Q2d send path feed the echo eliminator.
 */
final class TeleframeFacadeTest extends IngestTestCase
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
        return [5];
    }

    private const ACCOUNT = 5;

    private const USER_ID = 501558149;

    private Teleframe $teleframe;

    protected function setUp(): void
    {
        parent::setUp();

        // The handler table is a PROCESS-GLOBAL static that the service
        // provider binds as a container singleton, and matching is
        // first-match-wins. Four methods below register into it, so without
        // this reset the registrations survive into every later test that
        // dispatches through the real pipeline in the same process.
        //
        // Measured, not assumed: at HEAD, run alone this file's pipeline test
        // reads 0 registered handlers, and after the registering methods it
        // reads 4 (1+2+1, matching the four test bodies). Under
        // --order-by=reverse it failed outright (expected [5], got [] at :95),
        // and random seeds 3, 4 and 5 of 5 failed -- it was green only by the
        // accident of PHPUnit's default declaration order.
        // Pattern copied from tests/App/AppReachabilityIntakeTest.php.
        HandlerRegistry::reset();

        $this->teleframe = $this->app->make(Teleframe::class);
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();

        parent::tearDown();
    }

    public function test_ingest_delegates_to_the_real_ingestor(): void
    {
        // A storeable constructor delegates to the real ingestor: the root
        // is the hydrated curated model committed under the tenant.
        $root = $this->teleframe->ingest([
            '_' => 'message',
            'id' => 30,
            'peer_id' => ['_' => 'peerUser', 'user_id' => 5],
            'date' => 1_700_000_030,
            'message' => 'hello',
            'out' => false,
        ], self::ACCOUNT);

        self::assertInstanceOf(TfMessage::class, $root);
        self::assertSame(30, $root->id);
        self::assertSame(
            1,
            DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 30)->count(),
            'the facade really ingested into the curated messages truth',
        );

        // A constructor with no curated surface is null — nothing storeable.
        $none = $this->teleframe->ingest([
            '_' => 'user',
            'id' => self::USER_ID,
            'first_name' => 'Reza',
        ], self::ACCOUNT);

        self::assertNull($none);
    }

    public function test_on_message_runs_the_real_pipeline_through_run(): void
    {
        $seen = [];
        $this->teleframe->onMessage(function (Update $u) use (&$seen) {
            $seen[] = $u->accountId;
        });

        $results = $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage', 'message' => 'hi'], 'account_id' => self::ACCOUNT],
        ]);

        self::assertSame([self::ACCOUNT], $seen);
        self::assertCount(1, $results);
    }

    public function test_constructor_routing_and_priority_via_facade(): void
    {
        $seen = [];
        $this->teleframe
            ->on('updateNewMessage', function () use (&$seen) {
                $seen[] = 'message';
            }, 5)
            ->onMessage(function () use (&$seen) {
                $seen[] = 'catchall';
            }, 1);

        $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage'], 'account_id' => self::ACCOUNT],
            ['update' => ['_' => 'updateChannelNewMessage'], 'account_id' => self::ACCOUNT],
        ]);

        self::assertSame(['message', 'catchall'], $seen);
    }

    public function test_send_writes_the_registry_the_echo_eliminator_consumes(): void
    {
        $this->teleframe->onMessage(function (): void {});

        $this->teleframe->send(self::ACCOUNT, ['random_id' => 'r-1', 'msg_id' => 99]);

        // The real dispatcher's echo eliminator reads the SAME PSR-16
        // registry — the reply's echo is eliminated, not re-handled.
        $results = $this->teleframe->run([
            ['update' => ['_' => 'updateNewMessage', 'random_id' => 'r-1'], 'account_id' => self::ACCOUNT],
        ]);
        self::assertSame([null], $results);
    }

    public function test_one_hop_call_forwards_to_the_owning_module(): void
    {
        // Teleclient::chat_ ... facade does not surface it — forwarded.
        $registry = $this->app->make(HandlerRegistry::class);
        $count = $this->teleframe->count();
        self::assertSame($registry->count(), $count);

        $dispatcher = $this->teleframe->resolve(UpdateDispatcher::class);
        self::assertInstanceOf(UpdateDispatcher::class, $dispatcher);
    }
}
