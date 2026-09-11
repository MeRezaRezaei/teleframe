<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\HandlerSink;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Subscriptions\UpdateStoredHandler;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use PHPUnit\Framework\TestCase;

/**
 * Friction I.1 dissolved: BOTH intake rows (Laravel ``UpdateStored`` event
 * and the bus consumer's matched entries) funnel through ONE pipeline.
 * Same registry, same onion — only the transport ahead of the uprate
 * differs.
 */
final class SubscriptionsTest extends TestCase
{
    private HandlerRegistry $registry;
    private UpdateDispatcher $dispatcher;
    private ArrayContainer $container;
    private InMemoryCache $sends;
    private int $fired = 0;

    protected function setUp(): void
    {
        $this->registry = new HandlerRegistry();
        $this->sends = new InMemoryCache();
        $this->container = new ArrayContainer();
        $this->container->set(Update::class, Update::fromBus([], 0));
        $this->dispatcher = new UpdateDispatcher($this->registry, new Pipeline(), $this->container, $this->sends);
    }

    public function test_event_path_dispatches_mirrored_updates_to_the_shared_pipeline(): void
    {
        $this->registry->onMessage(function (Update $u) {
            $this->fired++;
            self::assertSame('', $u->constructor()); // mirror has no constructor key
            self::assertSame('event', $u->source);
            self::assertSame(11, $u->accountId);
        });

        $stored = new UpdateStored(new \MeRezaRezaei\Teleframe\Schema\Generated\Models\TfUser(), 11);
        $handler = new UpdateStoredHandler($this->dispatcher);
        $handler($stored);

        self::assertSame(1, $this->fired);
    }

    public function test_bus_path_dispatches_raw_updates_to_the_shared_pipeline(): void
    {
        $this->registry->on('updateNewMessage', function (Update $u) {
            $this->fired++;
            self::assertSame('updateNewMessage', $u->constructor());
            self::assertSame('bus', $u->source);
            self::assertSame(3, $u->accountId);
        });

        $sink = new HandlerSink($this->dispatcher);
        $sink->handle(['_' => 'updateNewMessage', 'message' => 'hi'], '3');

        self::assertSame(1, $this->fired);
    }

    public function test_both_rows_share_one_handler_table(): void
    {
        $this->registry->on('*', function () {
            $this->fired++;
        });

        $sink = new HandlerSink($this->dispatcher);
        $sink->handle(['_' => 'updateNewMessage'], (string) 1);
        (new UpdateStoredHandler($this->dispatcher))(
            new UpdateStored(new \MeRezaRezaei\Teleframe\Schema\Generated\Models\TfUser(), 1),
        );

        self::assertSame(2, $this->fired);
    }
}