<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\TemporalWorker;
use PHPUnit\Framework\TestCase;

/**
 * A worker's (app, call) pairs must be representable on the wire.
 *
 * WHY THIS TEST EXISTS AT THE WORKER AND NOT ONLY AT define(). The
 * declare-time refusal lives in AppDefinition::define(), but a worker is never
 * handed app definitions — it is handed raw pairs, and on this repository those
 * come from `bin/teleframe-worker --app=… --call=…`. That path never calls
 * define(), so before the refusal was hoisted into assertPairRepresentable() an
 * unrepresentable name reached the worker unvalidated: it registered a workflow
 * type it could never serve, and then reported the schedule NOT SERVED by a
 * worker it had already registered.
 *
 * Each case below asserts the PAIR is refused. The operator-visible part — the
 * CLI printing the message instead of a stack trace — is exercised by
 * bin/teleframe-worker itself and is not re-tested here.
 */
final class WorkerPairRepresentabilityTest extends TestCase
{
    public function test_it_refuses_an_app_name_holding_the_wire_separator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('may not contain a space');

        AppDefinition::assertPairRepresentable('my app', 'health');
    }

    public function test_it_refuses_a_call_name_holding_the_wire_separator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('may not contain a space');

        AppDefinition::assertPairRepresentable('echo', 'health check');
    }

    public function test_it_refuses_a_blank_app_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a non-empty string');

        AppDefinition::assertPairRepresentable('   ', 'health');
    }

    public function test_it_refuses_a_blank_call_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a non-empty string');

        AppDefinition::assertPairRepresentable('echo', '');
    }

    /**
     * The refusal must arrive at CONSTRUCTION, not at first use. A worker that
     * validated lazily would already have opened its Roadrunner connection and
     * registered types before telling the operator the name was unusable.
     */
    public function test_the_worker_refuses_at_construction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TemporalWorker('tstore', [['my app', 'health']]);
    }

    public function test_a_representable_pair_is_accepted_and_still_serves_its_type(): void
    {
        // The SDK probe defaults to class_exists(), and the Temporal SDK is not
        // installed in the test environment, so servedTypes() would be empty
        // for want of the SDK rather than for want of a type. Probing true
        // isolates what this test is actually about: that a VALID pair still
        // registers, and the refusal has not over-reached.
        $worker = new TemporalWorker(
            'tstore',
            [['echo', 'health']],
            sdkProbe: static fn (): bool => true
        );

        self::assertContains(
            // Space-delimited by design — this is the very format the
            // unrepresentable-name refusal protects.
            'telegram.app.run-call echo health',
            $worker->servedTypes(),
            'a valid pair must still register its workflow type'
        );
    }

    /**
     * define() and the worker must not disagree about a pair that will actually
     * be served. Two answers to one question is how a pair passes the worker and
     * is then refused at arm time.
     *
     * NOTE THE DELIBERATE ASYMMETRY, which this test pins rather than hides:
     * define() refuses a spaced APP NAME only once the app declares a call,
     * because without one no wire type is ever built and the name is just a
     * registry key. A worker, by contrast, is always handed a (app, call) pair
     * and always serves that call, so the app name always reaches the wire and
     * is always refused. Asymmetry is correct; DISAGREEMENT would not be.
     */
    public function test_the_worker_and_define_refuse_the_same_pair_that_gets_served(): void
    {
        $defined = true;
        $constructed = true;

        try {
            AppDefinition::define(
                'my app',
                AppMode::Bot,
                [],
                [new AppCallSpec('health')]
            );
        } catch (\InvalidArgumentException) {
            $defined = false;
        }

        try {
            new TemporalWorker('tstore', [['my app', 'health']]);
        } catch (\InvalidArgumentException) {
            $constructed = false;
        }

        self::assertFalse($defined, 'define() must refuse a spaced app name that declares a call');
        self::assertFalse($constructed, 'the worker must refuse the same pair');
    }

    /**
     * Pins the tolerance itself. If this ever starts throwing, define() has
     * become stricter than its documented reason and will take down working
     * declarations — including from provider boot(), for every process.
     */
    public function test_define_still_tolerates_a_spaced_app_name_that_declares_no_call(): void
    {
        $definition = AppDefinition::define('my app', AppMode::Bot);

        self::assertSame(
            'my app',
            $definition->name,
            'an app that declares no call builds no wire type, so its name is not representability-bound'
        );
    }
}
