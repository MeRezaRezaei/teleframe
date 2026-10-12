<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Control;

use MeRezaRezaei\Teleframe\Control\RelayFilter;
use MeRezaRezaei\Teleframe\Control\RelayMessage;
use MeRezaRezaei\Teleframe\Control\RelayPipeline;
use PHPUnit\Framework\TestCase;

/**
 * W-18: the control-layer onion (RelayPipeline) previously had zero direct
 * tests — only transitive coverage through AccountRelay/RelayTerminal. These
 * pin the two core invariants: filters run in registration order and each
 * may rewrite (withMethod/withParams) or short-circuit (return without $next).
 */
final class RelayPipelineTest extends TestCase
{
    public function test_no_filters_forwards_straight_to_terminal(): void
    {
        $message = new RelayMessage(RelayMessage::OUTBOUND, 'messages.sendMessage', ['peer' => 'a']);
        $sink = null;

        $runner = (new RelayPipeline)->then([], $this->terminal($sink));
        $result = $runner($message);

        self::assertSame($message, $result);
        self::assertSame($message, $sink, 'with an empty chain the terminal must receive the original message');
    }

    public function test_filters_run_in_registration_order_and_terminal_last(): void
    {
        $order = [];
        $first = $this->recordingFilter($order, 'first');
        $second = $this->recordingFilter($order, 'second');
        $sink = null;

        $runner = (new RelayPipeline)->then(
            [$first, $second],
            $this->terminal($sink, $order),
        );
        $runner(new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', []));

        self::assertSame(['first', 'second', 'terminal'], $order, 'filters fold in registration order, terminal runs last');
        self::assertNotNull($sink);
    }

    public function test_filter_can_rewrite_method_before_next(): void
    {
        $rewrite = new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                return $next($message->withMethod('messages.sendMessage')->withParams(['peer' => 'b']));
            }
        };
        $sink = null;

        $runner = (new RelayPipeline)->then([$rewrite], $this->terminal($sink));
        $runner(new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', ['limit' => 5]));

        self::assertNotNull($sink);
        self::assertSame('messages.sendMessage', $sink->method, 'rewrites must reach the terminal');
        self::assertSame(['peer' => 'b'], $sink->params);
    }

    public function test_short_circuit_skips_later_filters_and_terminal(): void
    {
        $replacement = new RelayMessage(RelayMessage::INBOUND, 'updates.state', ['pts' => 42]);
        $drop = new class($replacement) implements RelayFilter
        {
            public function __construct(private readonly RelayMessage $replacement) {}

            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                return $this->replacement;
            }
        };
        $order = [];
        $later = $this->recordingFilter($order, 'later');
        $terminalCalled = false;

        $runner = (new RelayPipeline)->then(
            [$drop, $later],
            function (RelayMessage $message) use (&$terminalCalled): RelayMessage {
                $terminalCalled = true;

                return $message;
            },
        );
        $result = $runner(new RelayMessage(RelayMessage::OUTBOUND, 'messages.sendMessage', ['peer' => 'a']));

        self::assertSame($replacement, $result, 'a short-circuiting filter replaces the message');
        self::assertSame([], $order, 'later filters must not run after a short circuit');
        self::assertFalse($terminalCalled, 'the terminal must never see a short-circuited message');
    }

    public function test_short_circuit_drops_flow_without_calling_next(): void
    {
        $dropper = new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                return $message;
            }
        };
        $terminalCalled = false;

        $runner = (new RelayPipeline)->then(
            [$dropper],
            function (RelayMessage $message) use (&$terminalCalled): RelayMessage {
                $terminalCalled = true;

                return $message;
            },
        );
        $result = $runner(new RelayMessage(RelayMessage::INBOUND, 'updateNewMessage', []));

        self::assertFalse($terminalCalled, 'dropping = returning without $next; the terminal is bypassed');
        self::assertNotNull($result);
    }

    /**
     * @return callable(RelayMessage): RelayMessage
     */
    private function terminal(?RelayMessage &$sink, array &$order = []): callable
    {
        return function (RelayMessage $message) use (&$sink, &$order): RelayMessage {
            $sink = $message;
            $order[] = 'terminal';

            return $message;
        };
    }

    private function recordingFilter(array &$order, string $name): RelayFilter
    {
        return new class($order, $name) implements RelayFilter
        {
            /** @param list<string> $order */
            public function __construct(private array &$order, private readonly string $name) {}

            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                $this->order[] = $this->name;

                return $next($message);
            }
        };
    }
}
