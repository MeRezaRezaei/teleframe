<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\Store\Schedules\Worker\TemporalWorker;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\WorkflowTypeCatalog;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * DEFECT-2 — the worker verdict claimed coverage no process can verify.
 *
 * `WorkflowTypeCatalog::serves()` returned true for ANY well-formed
 * `telegram.app.run-call {app} {call}` pair, because `parseAppCall()` only asks
 * "is this string shaped like an app-call type". `TemporalSchedulePublisher`
 * prints that verdict per armed schedule, so the operator read:
 *
 *     worker: served by this package — run bin/teleframe-worker
 *       (registers workflow type telegram.app.run-call assistant status-ping)
 *
 * for a pair the operator may never have passed to
 * `bin/teleframe-worker --app=… --call=…`. And it CANNOT be: the type embeds the
 * app and call names, and `TemporalWorker::registerAppCallTypes()` registers
 * ONLY the declared pairs, deliberately refusing a wildcard ("a type string
 * this package does not know the (app, call) of is not a schedule this worker
 * can serve"). So the disclosure asserted a fact about a worker process that
 * does not exist in the process printing it.
 *
 * WHY THE FIX IS NOT `serves() → false`. That would break the legitimate
 * single-app install — a worker started for exactly that pair DOES serve it —
 * and would report a KNOWN GAP where there is a conditional requirement. The
 * requirement is what needed stating, so the verdict states it.
 */
final class AppCallWorkerDisclosureTest extends TestCase
{
    private WorkflowTypeCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalog = new WorkflowTypeCatalog;
    }

    #[Test]
    public function the_app_call_verdict_states_the_requirement_instead_of_claiming_coverage(): void
    {
        $type = WorkflowTypeCatalog::appCallType('assistant', 'status-ping');

        $verdict = $this->catalog->verdict($type);

        self::assertStringNotContainsString('served by this package', $verdict,
            'the app-call family has no unconditional coverage: the worker registers only declared pairs, '
            .'so this process cannot claim the type is served');

        self::assertStringContainsString('--app=assistant --call=status-ping', $verdict,
            'the verdict must name the exact requirement the operator has to meet');
        self::assertStringContainsString($type, $verdict, 'the verdict names the type it is about');
        self::assertStringContainsString('cannot verify a worker is running', $verdict,
            'the honest scope: this process dials nothing and holds no server connection');
    }

    #[Test]
    public function serves_is_unchanged_so_a_legitimate_single_app_install_still_reads_as_served(): void
    {
        // The refusal must not have been bought by flipping serves(): a worker
        // started for exactly this pair does serve it, and reporting it as a gap
        // would be its own false negative.
        self::assertTrue(
            $this->catalog->serves(WorkflowTypeCatalog::appCallType('assistant', 'status-ping')),
            'a well-formed app-call pair is still in the served family',
        );

        self::assertTrue($this->catalog->serves(WorkflowTypeCatalog::STORE_BACKUP),
            'the fixed types are registered unconditionally and are unaffected');
    }

    #[Test]
    public function the_disclosure_keeps_its_label_and_says_the_requirement_out_loud(): void
    {
        // disclosure() is what the armed status line actually embeds.
        $disclosure = $this->catalog->disclosure(WorkflowTypeCatalog::appCallType('echo', 'health'));

        self::assertStringStartsWith('worker: ', $disclosure);
        self::assertStringContainsString('--app=echo --call=health', $disclosure);
    }

    #[Test]
    public function an_unserved_third_party_type_is_still_reported_unserved(): void
    {
        // The fix must not have softened the case the catalog already got right.
        $verdict = $this->catalog->verdict('somebody.else:does work');

        self::assertStringContainsString('NOT SERVED', $verdict);
        self::assertStringContainsString('every tick of this schedule will fail', $verdict);
    }

    #[Test]
    public function the_fixed_types_keep_their_unchanged_one_liner(): void
    {
        // They ARE registered by TemporalWorker::register() with no operator
        // action, so the unconditional sentence is still true for them.
        self::assertStringContainsString(
            'served by this package',
            $this->catalog->verdict(WorkflowTypeCatalog::STORE_BACKUP),
        );
    }

    /**
     * The requirement the verdict names is the one the entry point implements —
     * asserted here so the sentence and the CLI cannot drift apart silently.
     */
    #[Test]
    public function the_named_requirement_is_the_one_the_entry_point_takes(): void
    {
        $entry = file_get_contents(dirname(__DIR__, 2).'/bin/teleframe-worker');

        self::assertIsString($entry);
        self::assertStringContainsString('--app', $entry);
        self::assertStringContainsString('--call', $entry);
        self::assertStringContainsString(TemporalWorker::ENTRY_POINT, $entry,
            'the entry point the fixed types name must be the one that takes those flags');
    }
}
