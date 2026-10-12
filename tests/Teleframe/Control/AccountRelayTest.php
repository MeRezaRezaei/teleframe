<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Control;

use MeRezaRezaei\Teleframe\Control\AccountRelay;
use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Control\RelayFilter;
use MeRezaRezaei\Teleframe\Control\RelayMessage;
use PHPUnit\Framework\TestCase;

/**
 * The control-layer middle seam: filters run on an onion for every message,
 * may rewrite or short-circuit (drop/synthetic reply), and the live scope
 * is resolved lazily — never dialed at construction. Pure plain-PHP; no
 * credentials anywhere.
 */
final class AccountRelayTest extends TestCase
{
    public function test_relay_runs_filters_in_onion_order(): void
    {
        $trace = [];
        $relay = $this->relay();

        $relay->push($this->traceFilter('first', $trace));
        $relay->push($this->traceFilter('second', $trace));

        $relay->send(new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', []));

        self::assertSame(['first:before', 'second:before', 'second:after', 'first:after'], $trace);
    }

    public function test_filter_rewrites_params_before_terminal_sees_them(): void
    {
        $seen = [];
        $relay = $this->relay();
        $relay->push(new class($seen) implements RelayFilter
        {
            /** @param array<string, mixed> $seen */
            public function __construct(private array &$seen) {}

            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                return $next($message->withParams(['limit' => 5, 'filtered' => true]));
            }
        });

        $message = $relay->send(
            new RelayMessage(RelayMessage::OUTBOUND, 'messages.search', []),
            function (RelayMessage $m) use (&$seen): RelayMessage {
                $seen['params'] = $m->params;

                return $m;
            },
        );

        self::assertSame(['limit' => 5, 'filtered' => true], $message->params);
        self::assertSame(['limit' => 5, 'filtered' => true], $seen['params']);
    }

    public function test_filter_can_drop_inbound_data_before_the_client(): void
    {
        $relay = $this->relay();
        $relay->push($this->dropFilter('messages.getMessageEditData'));

        $result = $relay->send(new RelayMessage(RelayMessage::INBOUND, 'messages.getMessageEditData', []));

        // Dropped: replaced by a marker the terminal would have sent instead.
        self::assertSame('dropped', $result->method);
    }

    public function test_inbound_and_outbound_share_one_chain_with_direction(): void
    {
        $directions = [];
        $relay = $this->relay();
        $relay->push($this->directionCollector($directions));

        $relay->send(new RelayMessage(RelayMessage::OUTBOUND, 'a', []));
        $relay->send(new RelayMessage(RelayMessage::INBOUND, 'b', []));

        self::assertSame([RelayMessage::OUTBOUND, RelayMessage::INBOUND], $directions);
    }

    public function test_scope_is_resolved_lazily_and_never_at_construction(): void
    {
        $calls = 0;
        $relay = new AccountRelay(
            new RelayContext(1, 2, 'mine'),
            function () use (&$calls): string {
                $calls++;

                return 'live-scope';
            },
        );

        self::assertSame(0, $calls, 'constructor must not dial');
        self::assertSame('live-scope', $relay->scope());
        self::assertSame('live-scope', $relay->scope());
        self::assertSame(2, $calls, 'resolver is a fresh call per scope() — dialing is the caller\u2019s decision');
    }

    private function relay(): AccountRelay
    {
        return new AccountRelay(
            new RelayContext(1, 2, 'mine'),
            fn (): string => 'fake-scope',
        );
    }

    /** @param list<string> $trace */
    private function traceFilter(string $name, array &$trace): RelayFilter
    {
        return new class($name, $trace) implements RelayFilter
        {
            /** @param list<string> $trace */
            public function __construct(private string $name, private array &$trace) {}

            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                $this->trace[] = $this->name.':before';
                $result = $next($message);
                $this->trace[] = $this->name.':after';

                return $result;
            }
        };
    }

    private function dropFilter(string $method): RelayFilter
    {
        return new class($method) implements RelayFilter
        {
            public function __construct(private string $method) {}

            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                if ($message->isInbound() && $message->method === $this->method) {
                    return new RelayMessage($message->direction, 'dropped', []);
                }

                return $next($message);
            }
        };
    }

    /** @param list<string> $directions */
    private function directionCollector(array &$directions): RelayFilter
    {
        return new class($directions) implements RelayFilter
        {
            /** @param list<string> $directions */
            public function __construct(private array &$directions) {}

            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                $this->directions[] = $message->direction;

                return $next($message);
            }
        };
    }
}
