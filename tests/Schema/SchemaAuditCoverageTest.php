<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use MeRezaRezaei\Teleframe\Laravel\Console\SchemaAuditCommand;
use MeRezaRezaei\Teleframe\Schema\WireSchemaCoverage;

/**
 * CLAIM 2: the unsupported-method classification must be DISCOVERABLE.
 *
 * `WireSchemaCoverage` is a rich, honest classifier and until now no Artisan
 * command printed any of it — `grep -rn WireSchemaCoverage src/Laravel/Console/`
 * was empty. A limit that exists only in PHP is a limit a host discovers by
 * hitting it. These tests pin that `teleframe:schema-audit` now reports the
 * three families WITH their real counts, so the command and the classifier can
 * never drift apart silently.
 */
final class SchemaAuditCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(Kernel::class)->registerCommand(new SchemaAuditCommand);
    }

    /**
     * The rendered coverage block, read through the command's own entry point
     * so the test exercises the SAME string a host sees — not a private
     * formatter that could drift from what `handle()` prints.
     */
    private function coverage(): string
    {
        return SchemaAuditCommand::renderCoverageSection();
    }

    public function test_coverage_section_names_every_family_and_their_real_counts(): void
    {
        $out = $this->coverage();

        // Every family the classifier can emit a non-callable verdict for.
        self::assertStringContainsString(WireSchemaCoverage::NOT_IN_WIRE_MIRROR, $out);
        self::assertStringContainsString(WireSchemaCoverage::ENVELOPE, $out);
        self::assertStringContainsString(WireSchemaCoverage::SERVICE, $out);

        // And the real totals, read from the classifier itself rather than
        // hardcoded, so this test fails if either side moves.
        $expected = count(MethodRegistry::unsupportedMethods());
        self::assertSame(36, $expected, 'the unsupported total moved — the docs and this test must both be re-derived');
        self::assertStringContainsString((string) $expected, $out);
    }

    public function test_coverage_counts_match_the_classifier_family_by_family(): void
    {
        $out = $this->coverage();

        foreach ([
            WireSchemaCoverage::NOT_IN_WIRE_MIRROR,
            WireSchemaCoverage::ENVELOPE,
            WireSchemaCoverage::SERVICE,
        ] as $family) {
            $expected = 0;
            foreach (array_keys(MethodRegistry::unsupportedMethods()) as $name) {
                $expected += WireSchemaCoverage::classify($name) === $family ? 1 : 0;
            }

            self::assertStringContainsString(
                $family.' — '.$expected,
                $out,
                "the {$family} count printed by the command does not match the classifier ({$expected})",
            );
        }
    }

    /**
     * The 23 layer-gap methods, by name. A host must be able to ask the tool
     * about a SPECIFIC method, so a representative name from each namespace
     * group has to appear — that is the whole point of surfacing the list.
     */
    public function test_coverage_lists_actual_method_names(): void
    {
        $out = $this->coverage();

        foreach ([
            'communities.getPeerLinkRequests',
            'ephemeral.sendMessage',
            'auth.initFirebasePnvLogin',
            'messages.translateRichMessage',
            'invokeWithLayer',
            'req_pq',
            'destroy_auth_key',
        ] as $name) {
            self::assertStringContainsString($name, $out, "the coverage list must name {$name}");
        }
    }

    /**
     * A callable method must NOT appear in the unsupported list — otherwise the
     * section is noise and a host stops trusting it.
     */
    public function test_coverage_omits_callable_methods(): void
    {
        $out = $this->coverage();

        self::assertStringNotContainsString('account.getWallPapers', $out);
        self::assertStringNotContainsString('messages.sendMessage', $out);
    }

    /**
     * The real end-to-end path: the registered command's output carries the
     * section. The diff half of the report may legitimately report a
     * difference (that is what audit exit code 1 means); this asserts only on
     * the coverage text.
     */
    public function test_the_registered_command_output_carries_the_coverage_section(): void
    {
        Artisan::call('teleframe:schema-audit');
        $out = Artisan::output();

        self::assertStringContainsString(WireSchemaCoverage::NOT_IN_WIRE_MIRROR, $out);
        self::assertStringContainsString('invokeWithLayer', $out);
    }

    /**
     * The section must state the reason in words, not just a count: a host
     * reading only the command output should learn WHY a family is refused.
     */
    public function test_coverage_states_a_reason_per_family(): void
    {
        $out = $this->coverage();

        self::assertStringContainsString('api_full.tl', $out, 'the layer-gap cause must name the mirror file');
        self::assertStringContainsString('api.tl', $out, 'the layer-gap cause must name the catalogue file');
        self::assertStringContainsString('ENVELOPE', $out);
        self::assertStringContainsString('mtproto', $out);
    }
}
