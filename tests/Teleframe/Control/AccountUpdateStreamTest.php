<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Control;

use MeRezaRezaei\Teleframe\Control\AccountUpdateStream;
use MeRezaRezaei\Teleframe\Control\RelayFilter;
use MeRezaRezaei\Teleframe\Control\RelayMessage;
use PHPUnit\Framework\TestCase;

/**
 * The control-layer update stream: every update pulled from the reader
 * becomes an INBOUND RelayMessage and passes through the same filter onion
 * as AccountRelay, so a filter can rewrite (withParams) or drop (a synthetic
 * RelayMessage) data before the publisher — and the SessionRegistry lifecycle
 * (start / touch per live pulse / stop) is driven by injected closures.
 * Pure plain-PHP: no container, no credentials, no live bus.
 */
final class AccountUpdateStreamTest extends TestCase
{
    public function test_all_optional_deps_are_noop_defaults_constructing_without_container(): void
    {
        $stream = new AccountUpdateStream(1);

        self::assertSame(1, $stream->accountId);
        $stream->start();
        self::assertNull($stream->next());
        $stream->stop();
    }

    public function test_start_next_stop_orders_registry_lifecycle(): void
    {
        $calls = [];
        $stream = new AccountUpdateStream(
            7,
            self::reader([['_' => 'updateNewMessage', 'seq' => 1], ['_' => 'updateEditMessage', 'seq' => 2]]),
            null,
            self::recorder($calls, 'start'),
            self::recorder($calls, 'stop'),
            self::recorder($calls, 'touch'),
        );

        $stream->start();
        self::assertNotNull($stream->next());
        self::assertNotNull($stream->next());
        self::assertNull($stream->next(), 'drained reader yields null, not a third touch');
        $stream->stop();

        self::assertSame(['start(7,stream)', 'touch(7)', 'touch(7)', 'stop(7)'], $calls);
    }

    public function test_each_update_becomes_an_inbound_relay_message_with_payload_params(): void
    {
        $published = [];
        $stream = new AccountUpdateStream(
            7,
            self::reader([['_' => 'updateNewMessage', 'seq' => 9]]),
            static function (RelayMessage $message) use (&$published): void {
                $published[] = $message;
            },
        );

        $message = $stream->next();

        self::assertInstanceOf(RelayMessage::class, $message);
        self::assertSame(RelayMessage::INBOUND, $message->direction);
        self::assertSame('update', $message->method);
        self::assertSame(['_' => 'updateNewMessage', 'seq' => 9], $message->params);
        self::assertNull($message->raw);
        self::assertTrue($message->isInbound());
        self::assertCount(1, $published);
        self::assertSame($message, $published[0], 'publisher sees the very message the pipeline produced');
    }

    public function test_add_filter_returns_self_for_chaining(): void
    {
        $stream = new AccountUpdateStream(7);
        $filter = new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                return $next($message);
            }
        };

        self::assertSame($stream, $stream->addFilter($filter));
    }

    public function test_registered_filter_can_rewrite_params_before_the_publisher(): void
    {
        $published = [];
        $stream = new AccountUpdateStream(
            7,
            self::reader([['_' => 'updateNewMessage', 'secret' => 'leak', 'seq' => 9]]),
            static function (RelayMessage $message) use (&$published): void {
                $published[] = $message;
            },
        );
        $stream->addFilter(new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                $params = $message->params;
                unset($params['secret']);

                return $next($message->withParams($params));
            }
        });

        $message = $stream->next();

        self::assertSame(['_' => 'updateNewMessage', 'seq' => 9], $message->params);
        self::assertCount(1, $published);
        self::assertSame(['_' => 'updateNewMessage', 'seq' => 9], $published[0]->params);
        self::assertArrayNotHasKey('secret', $published[0]->params);
    }

    public function test_registered_filter_can_drop_an_update_before_the_publisher(): void
    {
        $published = [];
        $stream = new AccountUpdateStream(
            7,
            self::reader([['_' => 'updateNewMessage', 'seq' => 9]]),
            static function (RelayMessage $message) use (&$published): void {
                $published[] = $message;
            },
        );
        $stream->addFilter(new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                return new RelayMessage(RelayMessage::INBOUND, 'dropped', []);
            }
        });

        $message = $stream->next();

        self::assertSame('dropped', $message->method, 'control seam: data dropped before it left the stream');
        self::assertCount(1, $published);
        self::assertSame('dropped', $published[0]->method);
        self::assertSame([], $published[0]->params);
    }

    /**
     * @param  list<array<string, mixed>>  $payloads
     */
    private static function reader(array $payloads): callable
    {
        return static function () use (&$payloads): ?array {
            return $payloads === [] ? null : array_shift($payloads);
        };
    }

    /**
     * @param  list<string>  $calls
     * @return callable(mixed...): void
     */
    private static function recorder(array &$calls, string $label): callable
    {
        return static function (mixed ...$args) use (&$calls, $label): void {
            $calls[] = $label.'('.implode(',', $args).')';
        };
    }
}
