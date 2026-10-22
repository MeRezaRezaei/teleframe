<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\WireSchemaCoverage;
use MeRezaRezaei\Teleframe\Tests\Schema\Support\EncodableArgs;
use PHPUnit\Framework\TestCase;

/**
 * THE CATALOG-HONESTY TEST.
 *
 * The package promises "whatever telegram activity is needed ... using mtproto
 * call". A method that the registry names but that cannot be serialized is
 * worse than one it never mentioned, because the host finds out from an
 * encoder. Nothing asserted that the advertised set was the encodable set, so
 * the two drifted apart silently — and this test is the thing whose absence let
 * them.
 *
 * So the contract, stated once and checked from both ends:
 *
 *   1. Every method `MethodRegistry::isCallable()` calls callable ACTUALLY
 *      ENCODES. Not "has a signature" — the full argument tree is synthesised
 *      and handed to the real `TLEncoder`, because a signature can resolve
 *      while a nested constructor cannot.
 *   2. Every method that does NOT encode is named by
 *      `MethodRegistry::unsupportedMethods()` with a non-empty reason. This is
 *      what makes the package honest instead of merely quiet: the failure is
 *      attributed, in words, instead of surfacing as a type error.
 *
 * (1) and (2) together mean the two sets cannot disagree in either direction:
 * a method cannot be advertised-but-dead, nor quietly dropped.
 *
 * The three non-callable families are asserted as FAMILIES, not as a frozen
 * total, because the honest total legitimately moves when the wire mirror is
 * re-snapshotted. A count pinned here would fail the day that is done
 * correctly, and would train everyone to edit the number instead of asking
 * why. What is pinned is the rule that produces it.
 */
final class CataloguedMethodsAreEncodableTest extends TestCase
{
    protected function setUp(): void
    {
        MethodRegistry::load();
        EncodableArgs::flush();
        WireSchemaCoverage::flush();
    }

    /**
     * The headline assertion: advertised == encodable.
     *
     * Runs the real encoder over the whole advertised MTProto surface.
     */
    public function test_every_advertised_mtproto_method_actually_encodes(): void
    {
        $unencodable = [];
        $unresolvable = [];

        foreach ($this->mtprotoNames() as $name) {
            if (! MethodRegistry::isCallable($name)) {
                continue;
            }

            $args = EncodableArgs::forConstructor($name);
            if ($args === null) {
                $unresolvable[] = $name;

                continue;
            }

            try {
                $binary = TLEncoder::encodeObject($name, $args);
            } catch (\Throwable $e) {
                $unencodable[$name] = $e->getMessage();

                continue;
            }

            // A real constructor line always yields at least the 4-byte id.
            $this->assertGreaterThanOrEqual(
                4,
                strlen($binary),
                "[{$name}] encoded to nothing; a constructor must emit at least its id",
            );
        }

        $this->assertSame(
            [],
            array_keys($unencodable),
            "Methods the registry advertises as callable but the encoder rejects:\n"
            .implode("\n", array_map(
                static fn (string $n, string $m): string => "  - {$n}: {$m}",
                array_keys($unencodable),
                array_values($unencodable),
            )),
        );

        $this->assertSame(
            [],
            $unresolvable,
            'Methods the registry advertises as callable whose argument types no mirror '
            .'constructor satisfies, so no call could ever be built: '
            .implode(', ', $unresolvable)
            .' (unresolvable types: '.implode(', ', EncodableArgs::unresolvedTypes()).')',
        );
    }

    /**
     * The converse: nothing that fails to encode may be advertised.
     *
     * Deliberately does NOT go through `isCallable()`, so a regression that
     * marks a working method unsupported is caught too — the two halves of the
     * contract are checked independently and then meet.
     */
    public function test_every_unencodable_method_is_reported_unsupported_with_a_reason(): void
    {
        $silent = [];

        foreach ($this->mtprotoNames() as $name) {
            $args = EncodableArgs::forConstructor($name);
            $encodes = true;
            if ($args !== null) {
                try {
                    TLEncoder::encodeObject($name, $args);
                } catch (\Throwable) {
                    $encodes = false;
                }
            } else {
                $encodes = false;
            }

            $reason = MethodRegistry::unsupportedReason($name);

            if (! $encodes && $reason === null) {
                $silent[] = $name;
            }
            if ($encodes && $reason !== null) {
                $this->fail(
                    "[{$name}] encodes fine but the registry reports it unsupported: {$reason}\n"
                    .'A working method must never be demoted — that is the catalogue lying in the other direction.',
                );
            }
        }

        $this->assertSame(
            [],
            $silent,
            'These methods cannot be encoded and nothing says so. Each is advertised as callable and then '
            .'throws inside the encoder, which is the exact experience this test exists to end. Give each one a '
            .'reason in WireSchemaCoverage: '.implode(', ', $silent),
        );
    }

    /**
     * The whole unsupported set is non-empty, and every reason names its
     * evidence. A reason is only worth carrying if a host can act on it, so
     * "unsupported" with no sentence is treated as a defect, not a default.
     */
    public function test_every_unsupported_method_carries_an_actionable_reason(): void
    {
        $unsupported = MethodRegistry::unsupportedMethods();

        $this->assertNotSame([], $unsupported, 'The unsupported set is empty; the coverage classifier is inert.');

        foreach ($unsupported as $name => $reason) {
            $this->assertTrue(
                MethodRegistry::has($name),
                "[{$name}] is reported unsupported but is not in the catalogue at all.",
            );
            $this->assertFalse(MethodRegistry::isCallable($name));
            $this->assertGreaterThan(
                40,
                strlen($reason),
                "[{$name}] has a reason too short to tell a host anything: {$reason}",
            );
            // The reason must point at the file the verdict was read from.
            $this->assertTrue(
                str_contains($reason, 'sources/'),
                "[{$name}] reason cites no source file, so it cannot be checked: {$reason}",
            );
            $this->assertStringContainsString($name, $reason);
        }
    }

    /**
     * The three families, asserted by rule.
     *
     * Each is a distinct, nameable reason a host can be told — which is the
     * whole point. They are grouped here so that a future change to ANY of
     * them (a mirror re-snapshot, a layer move) shows up as a specific,
     * readable failure rather than a count drifting by two.
     */
    public function test_unsupported_methods_fall_into_the_three_explainable_families(): void
    {
        $families = [];
        foreach (array_keys(MethodRegistry::unsupportedMethods()) as $name) {
            $families[WireSchemaCoverage::classify($name)][] = $name;
        }

        // 1. Generic request envelopes: never endpoints. `invokeWithLayer` and
        //    `initConnection` are the two the client itself builds, so they are
        //    named explicitly — they are the most likely to be "fixed" by
        //    someone who reads "unsigned" as a bug. Envelopes are a SHAPE, not
        //    a blocked verdict: the ones the client hand-builds encode, so they
        //    are asserted through the envelope seam rather than through the
        //    unsupported set (which by definition holds only what cannot be
        //    sent). Same classifier, same names, no weakening.
        $envelopes = array_keys(WireSchemaCoverage::envelopes($this->mtprotoNames()));
        $this->assertContains('invokeWithLayer', $envelopes);
        $this->assertContains('initConnection', $envelopes);
        $this->assertContains('invokeWithTakeout', $envelopes);

        // An envelope that the client hand-builds is a real constructor on the
        // wire, and still not something a host can dial: binding `query:!X`
        // needs the wrapped request. Both halves are asserted, so neither can
        // quietly replace the other — and a *toy* signature leaking from
        // another test cannot make one of these look host-dialable again.
        foreach (['invokeWithLayer', 'initConnection'] as $wrapper) {
            $this->assertSame(
                WireSchemaCoverage::ENVELOPE,
                WireSchemaCoverage::classify($wrapper),
                "[{$wrapper}] is no longer classified as a generic request wrapper.",
            );
            $this->assertTrue(WireSchemaCoverage::isEncodable($wrapper), "[{$wrapper}] left the wire mirror.");
            $this->assertTrue(WireSchemaCoverage::clientBuilt($wrapper), "[{$wrapper}] is no longer client-built.");
            $this->assertNull(
                EncodableArgs::forConstructor($wrapper),
                "[{$wrapper}] became synthesisable by name; its generic binding is what a host cannot supply.",
            );
            $this->assertNotEmpty((string) MethodRegistry::unsupportedReason($wrapper));
        }

        // 2. Service requests the connection layer owns.
        $this->assertContains('req_pq', $families[WireSchemaCoverage::SERVICE] ?? []);
        $this->assertContains('destroy_auth_key', $families[WireSchemaCoverage::SERVICE] ?? []);

        // 3. Layer gap: catalogued at 229, absent from the 227 wire mirror.
        $this->assertContains('communities.create', $families[WireSchemaCoverage::NOT_IN_WIRE_MIRROR] ?? []);
        $this->assertContains('ephemeral.sendMessage', $families[WireSchemaCoverage::NOT_IN_WIRE_MIRROR] ?? []);
        $this->assertContains('messages.translateRichMessage', $families[WireSchemaCoverage::NOT_IN_WIRE_MIRROR] ?? []);
        $this->assertContains('auth.initFirebasePnvLogin', $families[WireSchemaCoverage::NOT_IN_WIRE_MIRROR] ?? []);

        // Whole namespaces, not just samples: these two are dead in full, and
        // a partial reappearance should be as loud as a partial disappearance.
        foreach ($families[WireSchemaCoverage::NOT_IN_WIRE_MIRROR] ?? [] as $name) {
            if (str_starts_with($name, 'communities.')) {
                $this->assertSame(
                    WireSchemaCoverage::NOT_IN_WIRE_MIRROR,
                    WireSchemaCoverage::classify($name),
                    "[{$name}] is back in the wire mirror — update this test's expectations deliberately.",
                );
            }
        }
    }

    /**
     * Bot API methods are never gated: they are JSON/HTTP, not TL-encoded, so
     * the mirror has no say in them. If this ever fails, the coverage check has
     * leaked across transports and is under-reporting.
     */
    public function test_bot_api_methods_are_never_gated_by_the_tl_mirror(): void
    {
        $botApi = 0;
        foreach ($this->allNames() as $name) {
            if (MethodRegistry::apiOf($name) !== 'bot-http') {
                continue;
            }
            $botApi++;
            $this->assertTrue(
                MethodRegistry::isCallable($name),
                "[{$name}] is a Bot API method and must stay callable regardless of the TL mirror.",
            );
            $this->assertNull(MethodRegistry::unsupportedReason($name));
        }

        $this->assertGreaterThan(0, $botApi, 'No bot-http methods found; the test is not exercising anything.');
    }

    /**
     * `callableMethods()` is the set a host should gate on, and it is exactly
     * the catalogue minus the reported-unsupported set.
     */
    public function test_callable_methods_is_the_catalogue_minus_the_unsupported_set(): void
    {
        $callable = MethodRegistry::callableMethods();
        $unsupported = MethodRegistry::unsupportedMethods();
        $all = $this->allNames();

        $this->assertCount(count($all) - count($unsupported), $callable);
        $this->assertSame([], array_intersect($callable, array_keys($unsupported)));
        $this->assertSame([], array_diff($callable, $all));

        // A host reading this must never be handed a name that does not exist.
        foreach ($callable as $name) {
            $this->assertTrue(MethodRegistry::has($name), "[{$name}] is advertised but not catalogued.");
        }
    }

    /**
     * The layer numbers quoted in a reason are read from the sources, and the
     * two they quote really do differ. A reason that cites matching layers
     * would be explaining a gap that does not exist.
     */
    public function test_the_layer_gap_reason_quotes_two_real_differing_layers(): void
    {
        $catalogueLayer = WireSchemaCoverage::layerOf('api.tl');
        $mirrorLayer = WireSchemaCoverage::layerOf('api_full.tl');

        $this->assertGreaterThan(0, $catalogueLayer, 'api.tl declares no layer; reasons would quote 0.');
        $this->assertGreaterThan(0, $mirrorLayer, 'api_full.tl declares no layer; reasons would quote 0.');
        $this->assertNotSame(
            $catalogueLayer,
            $mirrorLayer,
            'The catalogue source and the wire mirror now declare the same layer. If that is deliberate, the '
            .'NOT_IN_WIRE_MIRROR family should be empty and this test should be replaced, not relaxed.',
        );

        $reason = (string) MethodRegistry::unsupportedReason('communities.create');
        $this->assertStringContainsString("layer {$catalogueLayer}", $reason);
        $this->assertStringContainsString("layer {$mirrorLayer}", $reason);
    }

    /**
     * This class must not reach for a regex: `src/Schema` is on the allow-list,
     * but the classifier mirrors TLRegistry's loader rule, and a regex here
     * would be a second, driftable implementation of "what is a mirror line".
     */
    public function test_the_classifier_uses_no_regex(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../src/Schema/WireSchemaCoverage.php');

        $this->assertStringNotContainsString('preg_', $source);
    }

    /**
     * The GENERATED artefact carries the same verdict as the live classifier.
     *
     * `methods-mtproto.json` is generated by `bin/generate-method-schema.php`,
     * which stamps each method with a `wire` block so a host reading the file
     * itself is told the truth and not only a host calling the registry. That
     * makes the file a second place the answer lives, which is exactly the kind
     * of thing that rots, so it is checked against
     * `MethodRegistry::unsupportedMethods()` here — and the envelope-level
     * counts are checked against the two sets they summarise.
     *
     * A failure here means the committed artefact is stale: re-run
     * `php bin/generate-method-schema.php`.
     */
    public function test_the_generated_artifact_carries_the_same_verdict_as_the_classifier(): void
    {
        $envelope = json_decode(
            (string) file_get_contents(SchemaArtifacts::path('methods-mtproto.json')),
            true,
        );
        $this->assertIsArray($envelope);

        $stale = [];
        $unsupported = MethodRegistry::unsupportedMethods();
        foreach ((array) $envelope['methods'] as $name => $raw) {
            $wire = (array) ((array) $raw)['wire'];
            $this->assertArrayHasKey('callable', $wire, "[{$name}] has no wire.callable stamp; artefact is stale.");

            $shouldBeCallable = ! isset($unsupported[$name]);
            if ((bool) $wire['callable'] !== $shouldBeCallable) {
                $stale[] = "{$name}: artifact says callable=".var_export($wire['callable'], true)
                    .', registry says '.($shouldBeCallable ? 'callable' : 'unsupported');
            }
            if (! $shouldBeCallable) {
                $this->assertSame(
                    WireSchemaCoverage::classify($name),
                    $wire['blocked_by'] ?? null,
                    "[{$name}] wire.blocked_by disagrees with the classifier.",
                );
                $this->assertNotEmpty((string) ($wire['reason'] ?? ''), "[{$name}] has no reason in the artifact.");
            }
        }

        $this->assertSame(
            [],
            $stale,
            'methods-mtproto.json disagrees with MethodRegistry ('.count($stale)." entries).\n"
            ."Re-run: php bin/generate-method-schema.php\n".implode("\n", $stale),
        );

        $envelopeWire = (array) ($envelope['wire'] ?? []);
        $callable = MethodRegistry::callableMethods();
        $botApi = array_filter(
            $this->allNames(),
            static fn (string $n): bool => MethodRegistry::apiOf($n) === 'bot-http',
        );
        $mtprotoCallable = count($callable) - count($botApi);

        $this->assertSame($mtprotoCallable, $envelopeWire['callable_count'] ?? null, 'Artifact callable_count is stale.');
        $this->assertSame(
            count($unsupported),
            $envelopeWire['unsupported_count'] ?? null,
            'Artifact unsupported_count is stale.',
        );
        $this->assertSame(
            WireSchemaCoverage::layerOf('api_full.tl'),
            $envelopeWire['mirror_layer'] ?? null,
            'Artifact mirror_layer is stale.',
        );
    }

    /** @return list<string> */
    private function mtprotoNames(): array
    {
        $names = [];
        foreach ($this->allNames() as $name) {
            if (MethodRegistry::apiOf($name) === 'mtproto') {
                $names[] = $name;
            }
        }

        return $names;
    }

    /** @return list<string> */
    private function allNames(): array
    {
        static $names = null;
        if ($names !== null) {
            return $names;
        }

        foreach (['methods-mtproto.json', 'methods-botapi.json'] as $file) {
            $decoded = json_decode((string) file_get_contents(SchemaArtifacts::path($file)), true);
            foreach (array_keys((array) ($decoded['methods'] ?? [])) as $name) {
                $names[] = (string) $name;
            }
        }

        return $names;
    }
}
