<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Events;

use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramGapDetected;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramResynced;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramSessionRevoked;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramUpdateReceived;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stringable;

/**
 * A host listener that throws must not break the wire — and must not vanish.
 *
 * These four dispatchers all had an empty `catch (\Throwable) {}`. The swallow
 * is correct: they are raised from inside the MTProto read loop and from the
 * webhook controller, so a throwing listener would stop the connection, or turn
 * a 200 into a non-200 — and Telegram RETRIES a non-200, delivering the same
 * update forever.
 *
 * But the swallow was total. A host whose `TelegramUpdateReceived` listener
 * started throwing got no exception, no log line, and no signal: the update
 * simply disappeared, and the account looked asleep. The package's own rule
 * elsewhere is "log, never swallow" (see UpdateDispatcher), where a failing
 * handler is rethrown AND logged. Here the rethrow would be wrong, so the log
 * is the whole point.
 *
 * This test therefore pins BOTH halves: the wire is protected, and the failure
 * is reported.
 */
final class TelegramEventListenerFailureTest extends TestCase
{
    // ---- the two halves of the contract ---------------------------------

    public function test_a_throwing_listener_does_not_escape_the_dispatcher(): void
    {
        $this->bindSpy();
        Event::listen(TelegramUpdateReceived::class, static function (): void {
            throw new RuntimeException('host handler exploded');
        });

        // Must NOT throw: this runs on the read loop / inside a webhook.
        TelegramUpdateReceived::dispatch(['_update' => 1], 'bot-token', 7, 'test');

        $this->assertTrue(true, 'dispatch() must never propagate a listener failure');
    }

    public function test_a_throwing_listener_is_reported_at_error_level_by_name(): void
    {
        $spy = $this->bindSpy();
        Event::listen(TelegramUpdateReceived::class, static function (): void {
            throw new RuntimeException('host handler exploded');
        });

        TelegramUpdateReceived::dispatch(['_update' => 1], 'bot-token', 7, 'test');

        $log = $spy->everything();
        $this->assertStringContainsString('error', $log, 'a failed host listener must be logged at error level');
        $this->assertStringContainsString('teleframe.event.listener_failed', $log);
        $this->assertStringContainsString('TelegramUpdateReceived', $log, 'the log must name WHICH event failed');
        $this->assertStringContainsString('host handler exploded', $log, 'the throwable must travel with the report');
    }

    public function test_every_telegram_event_reports_rather_than_swallows_silently(): void
    {
        $spy = $this->bindSpy();

        foreach ([
            TelegramUpdateReceived::class,
            TelegramGapDetected::class,
            TelegramResynced::class,
            TelegramSessionRevoked::class,
        ] as $eventClass) {
            Event::listen($eventClass, static function () use ($eventClass): void {
                throw new RuntimeException('boom in '.$eventClass);
            });
        }

        // One dispatch per event, each with a listener that throws.
        TelegramUpdateReceived::dispatch(['_update' => 1]);
        TelegramGapDetected::dispatch('gap', []);
        TelegramResynced::dispatch([], 7);
        TelegramSessionRevoked::dispatch(7);

        $log = $spy->everything();
        foreach ([
            TelegramUpdateReceived::class,
            TelegramGapDetected::class,
            TelegramResynced::class,
            TelegramSessionRevoked::class,
        ] as $eventClass) {
            $short = substr((string) strrchr($eventClass, '\\'), 1);
            $this->assertStringContainsString(
                $short,
                $log,
                $eventClass.' must report a failing listener rather than swallow it',
            );
        }
        $this->assertSame(4, substr_count($log, 'teleframe.event.listener_failed'), 'exactly one report per failed dispatch');
    }

    public function test_a_listener_that_does_not_throw_logs_nothing(): void
    {
        $spy = $this->bindSpy();
        $seen = 0;
        Event::listen(TelegramUpdateReceived::class, static function () use (&$seen): void {
            $seen++;
        });

        TelegramUpdateReceived::dispatch(['_update' => 1]);

        $this->assertSame(1, $seen, 'the listener must still run');
        $this->assertStringNotContainsString('listener_failed', $spy->everything(), 'a healthy dispatch must be silent');
    }

    public function test_the_packages_silence_guarantee_holds_when_no_logger_is_bound(): void
    {
        // Unbind: this is the default state of a host that never configured
        // logging, and the package's contract is byte-for-byte silence there.
        $this->app->forgetInstance(LoggerInterface::class);
        $this->app->offsetUnset(LoggerInterface::class);

        Event::listen(TelegramUpdateReceived::class, static function (): void {
            throw new RuntimeException('host handler exploded');
        });

        // Must not throw, and must not attempt to log through a missing
        // binding — a failure to resolve the logger must not escalate.
        TelegramUpdateReceived::dispatch(['_update' => 1]);

        $this->assertTrue(true, 'an unconfigured logger must neither throw nor break the dispatch');
    }

    // ---- doubles ---------------------------------------------------------

    private function bindSpy(): RecordingLogSpy
    {
        $spy = new RecordingLogSpy;
        $this->app->instance(LoggerInterface::class, $spy);

        return $spy;
    }
}

/**
 * The repo's existing PSR-3 capture convention (see
 * PassportAnonymousLoginFlowTest's RecordingLogSpy).
 */
final class RecordingLogSpy extends AbstractLogger
{
    /** @var list<string> */
    public array $lines = [];

    /** @param array<string, mixed> $context */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->lines[] = (string) $level.' '.(string) $message.' '.(string) json_encode(
            array_map(
                static fn (mixed $v): string => is_object($v) ? $v::class.': '.$v->getMessage() : (string) $v,
                $context,
            ),
        );
    }

    public function everything(): string
    {
        return implode("\n", $this->lines);
    }
}
