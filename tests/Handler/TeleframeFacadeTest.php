<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * Facade delegates to the real module singletons (compose, never own) —
 * wire the resolver, run a constructor-routed handler through the REAL
 * pipeline, and let the Q2d send path feed the echo eliminator.
 */
final class TeleframeFacadeTest extends IngestTestCase
{
    private const ACCOUNT = 5;

    private const USER_ID = 501558149;

    private Teleframe $teleframe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teleframe = $this->app->make(Teleframe::class);
    }

    public function test_ingest_delegates_to_the_real_ingestor(): void
    {
        $root = $this->teleframe->ingest([
            '_' => 'user',
            'flags' => (1 << 0) | (1 << 1),
            'id' => self::USER_ID,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
        ], self::ACCOUNT);

        self::assertInstanceOf(\MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel::class, $root);

        $user = $this->teleframe->user(self::ACCOUNT, self::USER_ID);
        self::assertInstanceOf(TlUser::class, $user);
    }

    public function test_on_message_runs_the_real_pipeline_through_run(): void
    {
        $seen = [];
        $this->teleframe->onMessage(function (\MeRezaRezaei\Teleframe\Handler\Update $u) use (&$seen) {
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
        $this->teleframe->onMessage(function (): void {
        });

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