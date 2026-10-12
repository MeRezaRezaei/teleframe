<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\Handler;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use PHPUnit\Framework\TestCase;

final class HandlerRegistryTest extends TestCase
{
    public function test_handlers_are_data(): void
    {
        $handler = new Handler('updateNewMessage', 'App\\Handler', 2);

        self::assertSame('updateNewMessage', $handler->match);
        self::assertSame('App\\Handler', $handler->handler);
        self::assertSame(2, $handler->priority);
    }

    public function test_id_is_deterministic_for_string_callables(): void
    {
        $a = new Handler('updateNewMessage', 'App\\Handler');
        $b = new Handler('updateNewMessage', 'App\\Handler');

        self::assertSame($a->id(), $b->id());
        self::assertSame(16, strlen($a->id()));
    }

    public function test_on_registers_and_returns_self(): void
    {
        $registry = new HandlerRegistry;
        $return = $registry->on('updateNewMessage', fn () => null);

        self::assertSame($registry, $return);
        self::assertSame(1, $registry->count());
    }

    public function test_all_sorts_priority_desc_stable(): void
    {
        $registry = new HandlerRegistry;
        $registry
            ->on('a*', 'H1', 1)
            ->on('b*', 'H2', 5)
            ->on('c*', 'H3', 5)
            ->on('*', 'H4', 0);

        $ids = array_map(static fn (Handler $h) => $h->handler, $registry->all());

        self::assertSame(['H2', 'H3', 'H1', 'H4'], $ids);
    }

    public function test_on_message_is_catch_all(): void
    {
        $registry = new HandlerRegistry;
        $registry->onMessage('App\\AnyHandler');

        self::assertSame('*', $registry->all()[0]->match);
    }

    public function test_instance_is_always_the_same_singleton(): void
    {
        self::assertSame(HandlerRegistry::instance(), HandlerRegistry::instance());
    }

    public function test_reset_forgets_the_singleton(): void
    {
        HandlerRegistry::reset();
        $first = HandlerRegistry::instance();
        $first->on('*', 'App\\HotLoadedHandler');

        HandlerRegistry::reset();
        $second = HandlerRegistry::instance();

        self::assertNotSame($first, $second);
        self::assertSame(0, $second->count());
    }

    public function test_singleton_is_hot_for_late_registration(): void
    {
        HandlerRegistry::reset();
        $registry = HandlerRegistry::instance();

        $ids = array_map(static fn (Handler $h) => $h->handler, $registry->all());
        self::assertSame([], $ids);

        $registry->on('*', 'App\\HotLoadedHandler');

        $ids = array_map(static fn (Handler $h) => $h->handler, $registry->all());
        self::assertSame(['App\\HotLoadedHandler'], $ids);
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();
    }
}
