<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use MeRezaRezaei\Teleframe\Core\Contracts\SignalSink;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramGapDetected;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use PHPUnit\Framework\TestCase;

/**
 * Phase 0 Task 5: the poller's gap/resync signals must be observable with
 * no Laravel event dispatcher — via the injected sink. (Today they are
 * silently dropped standalone: TelegramGapDetected::dispatch guards on a
 * facade application that plain PHP never has.)
 */
final class UpdatePollerSignalSinkTest extends TestCase
{
    public function test_sink_is_optional_and_fluent(): void
    {
        $poller = new UpdatePollerService();

        self::assertSame($poller, $poller->withSignalSink(new RecordingSink()));
        self::assertInstanceOf(UpdatePollerService::class, new UpdatePollerService(signalSink: new RecordingSink()));
    }

    public function test_signal_method_forwards_to_sink_without_laravel(): void
    {
        $sink = new RecordingSink();
        $poller = (new UpdatePollerService())->withSignalSink($sink);

        // No Laravel app exists — constructing and holding the poller must
        // not fatal.
        self::assertNotNull($poller);
        self::assertSame([], $sink->gaps);
    }
}

final class RecordingSink implements SignalSink
{
    public array $gaps = [];
    public array $resyncs = [];

    public function gapDetected(string $kind, array $context): void
    {
        $this->gaps[] = ['kind' => $kind, 'context' => $context];
    }

    public function resynced(array $state, int $accountId): void
    {
        $this->resyncs[] = ['state' => $state, 'account' => $accountId];
    }
}