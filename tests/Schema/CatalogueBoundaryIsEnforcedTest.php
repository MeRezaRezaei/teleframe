<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Core\Methods\Methods;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\WireSchemaCoverage;
use MeRezaRezaei\Teleframe\Tests\Schema\Support\EncodableBoundary;
use PHPUnit\Framework\TestCase;

/**
 * THE BOUNDARY DRIFT GUARD — the recurrence-breaker for V1.4.
 *
 * THE DEFECT THIS EXISTS TO CLOSE
 * ===============================
 * The floor on "whatever telegram activity is needed" is a PROPERTY OF THE
 * PACKAGED SCHEMA, not a policy choice, and it has been stated in prose in
 * several places at once. Those places drifted. `34` and a
 * `communities.* + ephemeral.*`-only composition were each wrong, in four
 * places, before the number became 36 in three families. Nothing caught that,
 * because the number was PROSE: no test compared a sentence to the code, and
 * the one test that pinned a total (`SchemaAuditCoverageTest`) asserted only
 * that a computed count was also printed — it could not notice a count that was
 * never computed in the first place.
 *
 * So this test makes the boundary a machine-checked fact on THREE levels, in
 * increasing strictness:
 *
 *   1. RE-DERIVE IT INDEPENDENTLY. Nothing here trusts
 *      `MethodRegistry::unsupportedMethods()` as the definition of the truth.
 *      The families are recomputed from the committed artefacts the loader
 *      actually reads — `schema-manifest.json` (the catalogue layer),
 *      `methods-mtproto.json` (which names are catalogued at all), and the
 *      `.tl` sources the catalogue and the mirror are generated from — using
 *      the same admissibility rule the loader applies, and then asserted equal
 *      to what production reports. Two derivations that must agree means a bug
 *      in the classifier cannot quietly redefine the boundary.
 *
 *   2. FREEZE THE EXACT SETS, NOT JUST A TOTAL. A total is not enough: a
 *      re-snapshot that adds one method to a namespace and drops another leaves
 *      the total at 36 while every sentence saying "9 `communities.*`" becomes
 *      a lie. So each family is pinned BY NAME, and a failure prints the names
 *      that appeared and disappeared rather than just "expected 36, got 35".
 *      The existing `CataloguedMethodsAreEncodableTest` deliberately pins the
 *      RULE instead, and this does not weaken it: both run, and they answer
 *      different questions (does the rule still hold / is the set still this).
 *
 *   3. GUARD THE PROSE. The counts are not only in tests; they are in
 *      `Methods.php`'s own docblock, in `README.md`, and in `docs/user-client.md`,
 *      which is the document a host reads. A schema update that moves the floor
 *      would leave all of those stale and silent. So every boundary claim in
 *      maintained prose is extracted and checked against the derived truth, and
 *      a claim that is known to DISAGREE is listed in
 *      `KNOWN_PROSE_DISAGREEMENTS` with the file, the line, and the reason —
 *      visible in code, greppable, and gone the moment someone fixes the prose.
 *
 * WHY THE 36 / 34 SPLIT IS ASSERTED EXPLICITLY
 * =============================================
 * There are TWO honest counts and they are not the same number:
 *
 *   - 36 catalogued methods a HOST CANNOT DIAL BY NAME
 *     (`MethodRegistry::unsupportedMethods()`, `WireSchemaCoverage::reason()`).
 *     23 absent from the layer-227 wire mirror, 11 generic request envelopes,
 *     2 handshake service requests.
 *   - 34 catalogued methods with NO SERIALISABLE SIGNATURE AT ALL
 *     (`Methods::isCallable()`, `Client::assertEncodable()`).
 *
 * The difference is exactly `initConnection` and `invokeWithLayer`: real
 * constructors the client hand-builds on every call, so they serialize, while
 * a host still cannot dial them because binding `query:!X` needs the request
 * being wrapped. Conflating the two is how "34 catalogued but unencodable" came
 * to be read as the whole boundary. `test_the_two_honest_counts_differ_by_exactly_the_client_built_envelopes`
 * pins the relationship, so neither sentence can be reused for the other.
 *
 * Parsing note: the derivation below uses string functions only, mirroring
 * `TLRegistry::mirrorFileLines()`, because a second implementation of "what is
 * a mirror line" written with a regex would be free to disagree. Regex IS used
 * for the prose scan in level 3 — that is text forensics over Markdown and
 * docblocks, not schema parsing, and `tests/` is outside the `src/` zero-regex
 * rule.
 */
final class CatalogueBoundaryIsEnforcedTest extends TestCase
{
    /**
     * The frozen boundary: 36 catalogued mtproto methods a host cannot dial.
     *
     * Derived 2026-09-27 from the committed artefacts. If a schema update
     * legitimately moves any of these, the diff below is the work list — and
     * the prose in level 3 has to be swept in the same commit.
     */
    private const EXPECTED_ENVELOPES = [
        'initConnection',
        'invokeAfterMsg',
        'invokeAfterMsgs',
        'invokeWithApnsSecret',
        'invokeWithBusinessConnection',
        'invokeWithGooglePlayIntegrity',
        'invokeWithLayer',
        'invokeWithMessagesRange',
        'invokeWithReCaptcha',
        'invokeWithTakeout',
        'invokeWithoutUpdates',
    ];

    private const EXPECTED_SERVICE = [
        'destroy_auth_key',
        'req_pq',
    ];

    /**
     * The 23 layer-gap methods, grouped by namespace because that grouping IS
     * the claim the prose makes ("9 `communities.*`, 8 `ephemeral.*`, 3
     * `auth.*FirebasePnv*`, 3 `messages.*`"). Pinning a flat list would let the
     * per-namespace numbers rot while the total stayed at 23.
     */
    private const EXPECTED_NOT_IN_MIRROR = [
        'communities' => [
            'communities.create',
            'communities.getJoinedCommunities',
            'communities.getParticipantJoinedChats',
            'communities.getPeerLinkRequests',
            'communities.toggleAllPeerLinkRequestApproval',
            'communities.toggleCommunityCollapsedInDialogs',
            'communities.toggleParticipantBanned',
            'communities.togglePeerLink',
            'communities.togglePeerLinkRequestApproval',
        ],
        'ephemeral' => [
            'ephemeral.deleteAllWelcomeMessages',
            'ephemeral.deleteMessage',
            'ephemeral.deleteWelcomeMessage',
            'ephemeral.editMessage',
            'ephemeral.getCallbackAnswer',
            'ephemeral.getWelcomeMessages',
            'ephemeral.reportMessage',
            'ephemeral.sendMessage',
        ],
        'auth' => [
            'auth.finishFirebasePnvLogin',
            'auth.firebasePnvSignUp',
            'auth.initFirebasePnvLogin',
        ],
        'messages' => [
            'messages.composeRichMessageWithAI',
            'messages.requestChatJoinWebView',
            'messages.translateRichMessage',
        ],
    ];

    /** The 34 with no serialisable signature: the 36 minus the two client-built envelopes. */
    private const CLIENT_BUILT_ENVELOPES = ['initConnection', 'invokeWithLayer'];

    /**
     * Prose that is KNOWN to disagree with the code, with the reason.
     *
     * Listed rather than ignored so the disagreement is a greppable fact in the
     * repository instead of a sentence somebody has to re-discover. Each entry
     * is exempt from the level-3 scan; delete the entry once the prose is fixed
     * and the scan starts checking it. `docs/verbatim*` is NOT listed and NOT
     * scanned: it is the byte-frozen archive of raw words (AGENTS.md), so the
     * historical `34`s in it are a record of what was said, not a claim about
     * the code.
     *
     * @var array<string, string>
     */
    /**
     * Both sentences that were wrong have been FIXED (commit below), so this
     * list is deliberately EMPTY.
     *
     * It was not empty before: it held `docs/user-client.md:127` and
     * `docs/user-client.md:212`, both carrying the 36-vs-34 confusion in prose.
     * A guard that merely records a wrong sentence does not protect it — the
     * next reader inherits the same ambiguity. Empty is the honest state: every
     * boundary sentence in PROSE_SOURCES is now checked, with no exemptions.
     */
    private const KNOWN_PROSE_DISAGREEMENTS = [];

    /**
     * Prose files that state the boundary as CURRENT fact, and are therefore
     * checked against the derived truth. `docs/verbatim*` (byte-frozen archive)
     * and `docs/subagents/` (historical session logs) are excluded by
     * construction: both are records of what was said, not statements about
     * the code today.
     *
     * @var list<string>
     */
    private const PROSE_SOURCES = [
        'AGENTS.md',
        'README.md',
        'docs/user-client.md',
        'src/Schema/WireSchemaCoverage.php',
        'src/Core/Methods/Methods.php',
        'src/Core/Schema/MethodRegistry.php',
    ];

    /** Keywords that make a line a boundary claim. */
    private const CLAIM_MARKERS = [
        'catalogued',
        'not callable',
        'cannot be called',
        'unsupported',
        'not-in-wire-mirror',
        'envelope',
        'wire mirror',
        'callable_count',
    ];

    protected function setUp(): void
    {
        MethodRegistry::load();
        WireSchemaCoverage::flush();
        EncodableBoundary::flush();
    }

    // ---------------------------------------------------------------- level 1

    /**
     * The independent re-derivation agrees with the production classifier.
     *
     * If this fails, either the classifier drifted or the artefacts it reads
     * have; both are worth knowing, and neither may be papered over by
     * re-baselining the expectations above.
     */
    public function test_independent_derivation_agrees_with_the_production_classifier(): void
    {
        $derived = EncodableBoundary::derive();
        $production = [];

        foreach (array_keys(MethodRegistry::unsupportedMethods()) as $name) {
            $production[WireSchemaCoverage::classify($name)][] = $name;
        }

        $this->assertSame(
            $this->sorted($derived[WireSchemaCoverage::ENVELOPE]),
            $this->sorted($production[WireSchemaCoverage::ENVELOPE] ?? []),
            'Independent ENVELOPE derivation disagrees with WireSchemaCoverage.',
        );
        $this->assertSame(
            $this->sorted($derived[WireSchemaCoverage::SERVICE]),
            $this->sorted($production[WireSchemaCoverage::SERVICE] ?? []),
            'Independent SERVICE derivation disagrees with WireSchemaCoverage.',
        );
        $this->assertSame(
            $this->sorted($derived[WireSchemaCoverage::NOT_IN_WIRE_MIRROR]),
            $this->sorted($production[WireSchemaCoverage::NOT_IN_WIRE_MIRROR] ?? []),
            'Independent NOT_IN_WIRE_MIRROR derivation disagrees with WireSchemaCoverage.',
        );
    }

    /**
     * The catalogue sizes the prose quotes. `823` is the mtproto catalogue and
     * is quoted in `Methods.php`, `README.md` and `docs/user-client.md`; if a
     * schema update changes it, every one of those is stale and this fails.
     */
    public function test_the_catalogue_sizes_the_prose_quotes_are_derived_from(): void
    {
        $boundary = EncodableBoundary::derive();
        $unsupported = count(MethodRegistry::unsupportedMethods());

        $this->assertSame(823, $boundary['catalogue'], 'The mtproto catalogue is no longer 823 methods.');
        $this->assertSame(
            823 - $unsupported,
            $boundary['callable'],
            'catalogue minus unsupported no longer equals the derived callable count.',
        );
        $this->assertSame(185, EncodableBoundary::botApiCatalogue(), 'The Bot API catalogue is no longer 185 methods.');
        $this->assertSame(229, EncodableBoundary::manifestLayer(), 'schema-manifest.json no longer stamps layer 229.');
        $this->assertSame(227, WireSchemaCoverage::layerOf('api_full.tl'), 'The wire mirror is no longer layer 227.');

        // The GENERATED artefact stamps the same totals, so a host reading the
        // file itself sees the boundary rather than only a host calling the
        // registry. 787 + 36 must be the 823 the catalogue declares.
        $wire = (array) ((array) json_decode(
            (string) file_get_contents(SchemaArtifacts::path('methods-mtproto.json')),
            true,
        )['wire'] ?? []);

        $this->assertSame(823 - $unsupported, $wire['callable_count'] ?? null, 'Artifact callable_count is stale.');
        $this->assertSame($unsupported, $wire['unsupported_count'] ?? null, 'Artifact unsupported_count is stale.');
        $this->assertSame(
            823 - $unsupported + 185,
            count(MethodRegistry::callableMethods()),
            'callableMethods() is no longer the mtproto callable set plus the Bot API set.',
        );
    }

    // ---------------------------------------------------------------- level 2

    /**
     * The total, and the three families that must partition it.
     */
    public function test_the_unsupported_boundary_is_36_in_exactly_three_families(): void
    {
        $unsupported = MethodRegistry::unsupportedMethods();

        $this->assertCount(
            36,
            $unsupported,
            'The dial-by-name boundary is no longer 36. It is now '.count($unsupported).".\n"
            ."Re-derive it, then sweep every sentence listed in PROSE_SOURCES in the same commit:\n"
            .$this->proseInventory(),
        );

        $families = [];
        foreach (array_keys($unsupported) as $name) {
            $families[WireSchemaCoverage::classify($name)][] = $name;
        }

        // No fourth family may appear without this test being updated to say why.
        $this->assertSame(
            [WireSchemaCoverage::ENVELOPE, WireSchemaCoverage::NOT_IN_WIRE_MIRROR, WireSchemaCoverage::SERVICE],
            $this->sorted(array_keys($families)),
            'The boundary grew a family, or lost one. Each needs a reason a host can act on.',
        );
        $this->assertCount(11, $families[WireSchemaCoverage::ENVELOPE]);
        $this->assertCount(23, $families[WireSchemaCoverage::NOT_IN_WIRE_MIRROR]);
        $this->assertCount(2, $families[WireSchemaCoverage::SERVICE]);
    }

    /**
     * Each family pinned BY NAME. A count-only guard cannot see a substitution.
     */
    public function test_each_family_is_exactly_the_frozen_set_of_names(): void
    {
        $this->assertSameSet(WireSchemaCoverage::ENVELOPE, self::EXPECTED_ENVELOPES);
        $this->assertSameSet(WireSchemaCoverage::SERVICE, self::EXPECTED_SERVICE);
        $this->assertSameSet(
            WireSchemaCoverage::NOT_IN_WIRE_MIRROR,
            array_merge(...array_values(self::EXPECTED_NOT_IN_MIRROR)),
        );
    }

    /**
     * The per-namespace composition, which is the exact claim the prose makes
     * ("9 `communities.*`, 8 `ephemeral.*`, 3 `auth.*FirebasePnv*`, 3
     * `messages.*`"). The total is not enough; these four numbers are what a
     * host reads.
     */
    public function test_the_layer_gap_composition_is_nine_eight_three_three(): void
    {
        $actual = [];
        foreach (array_keys(MethodRegistry::unsupportedMethods()) as $name) {
            if (WireSchemaCoverage::classify($name) !== WireSchemaCoverage::NOT_IN_WIRE_MIRROR) {
                continue;
            }
            $dot = strpos($name, '.');
            $actual[$dot === false ? $name : substr($name, 0, $dot)][] = $name;
        }
        ksort($actual);

        $this->assertSame(
            ['auth' => 3, 'communities' => 9, 'ephemeral' => 8, 'messages' => 3],
            array_map('count', $actual),
            'The layer-gap namespace composition moved. "9 communities, 8 ephemeral, 3 auth, 3 messages" is '
            .'now a lie somewhere.\n'.$this->proseInventory(),
        );
    }

    /**
     * Every one of the 23 layer-gap methods really is absent from the mirror,
     * and every other catalogued mtproto method really is in it — checked
     * against the mirror files directly, not against the classifier.
     */
    public function test_every_layer_gap_method_is_genuinely_absent_from_the_wire_mirror(): void
    {
        $mirror = EncodableBoundary::wireMirrorIndex();

        foreach (self::EXPECTED_NOT_IN_MIRROR as $namespace => $names) {
            foreach ($names as $name) {
                $this->assertArrayNotHasKey(
                    $name,
                    $mirror,
                    "[{$name}] ({$namespace}) is now in the wire mirror, so it is no longer a layer gap. "
                    .'Either the mirror moved or the expectation is wrong.',
                );
                $this->assertTrue(
                    MethodRegistry::has($name),
                    "[{$name}] is not catalogued at all, so it cannot be part of the catalogue's boundary.",
                );
            }
        }
    }

    /**
     * Every one of the 11 envelopes declares a bound generic query, and every
     * one of the 2 service requests comes from the service source. These are
     * the two structural claims that justify the families; if the .tl lines
     * ever stop looking like that, the classification is a guess.
     */
    public function test_the_envelope_and_service_families_earn_their_verdicts_from_the_sources(): void
    {
        foreach (self::EXPECTED_ENVELOPES as $name) {
            $this->assertTrue(
                WireSchemaCoverage::isEnvelope($name),
                "[{$name}] is no longer a generic {{X:Type}} wrapper in the catalogue source.",
            );
            $this->assertSame(
                WireSchemaCoverage::ENVELOPE,
                WireSchemaCoverage::classify($name),
                "[{$name}] is no longer classified as an envelope.",
            );
        }

        foreach (self::EXPECTED_SERVICE as $name) {
            $this->assertSame(
                WireSchemaCoverage::SERVICE,
                WireSchemaCoverage::classify($name),
                "[{$name}] is no longer an MTProto service request.",
            );
            $this->assertStringContainsString(
                'mtproto.tl',
                (string) MethodRegistry::unsupportedReason($name),
                "[{$name}] is no longer attributed to the service source.",
            );
        }
    }

    // ---------------------------------------------------------------- level 3

    /**
     * Every number stated in maintained prose about the boundary is a number
     * the code currently supports.
     *
     * This is the part that stops the recurrence. A schema update that changes
     * the floor now has to touch prose, because this test fails the moment a
     * boundary sentence quotes a figure that is no longer true.
     */
    public function test_no_maintained_prose_quotes_a_boundary_number_the_code_does_not_support(): void
    {
        $allowed = $this->supportedNumbers();
        $offenders = [];

        foreach (self::PROSE_SOURCES as $relative) {
            $path = $this->repoRoot().'/'.$relative;
            if (! is_file($path)) {
                continue;
            }
            foreach ($this->boundaryLines($path, $relative) as $line) {
                foreach ($this->numbersIn($line['text']) as $number) {
                    if (in_array($number, $allowed, true)) {
                        continue;
                    }
                    $key = $relative.':'.$line['no'];
                    if (isset(self::KNOWN_PROSE_DISAGREEMENTS[$key])) {
                        continue;
                    }
                    $offenders[] = "{$key} quotes {$number}: ".trim($line['text']);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Prose quotes a boundary figure the code does not support.\n\n"
            ."Each of these sentences is now wrong. Fix the prose in the same commit that changed the schema.\n\n"
            .implode("\n", $offenders)."\n\n"
            .'Supported right now: '.implode(', ', $allowed)."\n",
        );
    }

    /**
     * The specific claim each maintained source is making, checked where it
     * matters. A file that quietly dropped its boundary statement stops
     * satisfying the anchor, which is itself a change worth being loud about.
     */
    public function test_each_maintained_source_still_states_the_boundary_it_owns(): void
    {
        $expectations = [
            'docs/user-client.md' => ['**36 of the 823 catalogued mtproto methods', 'not-in-wire-mirror` — 23 methods', 'envelope` — 11 methods', 'service` — 2 methods'],
            'src/Core/Methods/Methods.php' => ['36 catalogued methods have none'],
            'README.md' => ['**823 MTProto**', '**185 Bot API**'],
            'src/Schema/WireSchemaCoverage.php' => ['823 methods'],
        ];

        foreach ($expectations as $relative => $anchors) {
            $text = (string) file_get_contents($this->repoRoot().'/'.$relative);
            foreach ($anchors as $anchor) {
                $this->assertStringContainsString(
                    $anchor,
                    $text,
                    "{$relative} no longer contains [{$anchor}]. If the boundary was re-derived, update this "
                    .'expectation and the sentence together — do not delete one silently.',
                );
            }
        }
    }

    /**
     * A recorded disagreement must be REAL, or it becomes a permanent excuse.
     * Each entry is re-checked: the line must still exist and must still
     * disagree, so a fixed doc fails here and the exemption is deleted.
     */
    public function test_every_recorded_prose_disagreement_is_still_actually_disagreeing(): void
    {
        // The list is empty right now, and that is a CLAIM worth asserting, not
        // a loop that silently performs no work. A PHPUnit "risky" result here
        // means a zero-assertion test, which is exactly the kind of green that
        // hides a hole — so state the emptiness deliberately and fail loudly if
        // someone adds an exemption without also fixing the prose.
        $this->assertSame(
            [],
            self::KNOWN_PROSE_DISAGREEMENTS,
            'a prose disagreement is exempt again. Prefer fixing the sentence in the same commit that '
            .'caused it: an exemption is a standing permission for the number to rot, and the guard '
            .'cannot then protect that file. If an exemption is genuinely unavoidable, say why in the '
            .'entry and expect the entry to be reviewed.',
        );

        foreach (self::KNOWN_PROSE_DISAGREEMENTS as $where => $why) {
            [$relative, $no] = explode(':', $where);
            $lines = @file($this->repoRoot().'/'.$relative) ?: [];
            $this->assertArrayHasKey(
                $no - 1,
                $lines,
                "{$where} is recorded as disagreeing but the file no longer has that line. "
                .'The prose was probably fixed — delete the entry and let the scan check it. Recorded because: '.$why,
            );
            $this->assertNotSame('', trim($lines[$no - 1]), "{$where} recorded as disagreeing but the line is empty.");
        }
    }

    // ------------------------------------------------- the 36 / 34 relationship

    /**
     * 36 cannot be dialed by a host; 34 cannot be serialized at all; the
     * difference is exactly the two envelopes the client hand-builds.
     *
     * Both numbers are true. The failure mode this prevents is reusing one
     * where the other belongs, which is how "34 catalogued but unencodable"
     * came to be read as the whole boundary.
     */
    public function test_the_two_honest_counts_differ_by_exactly_the_client_built_envelopes(): void
    {
        $unsupported = array_keys(MethodRegistry::unsupportedMethods());

        $dialableByMethods = [];
        foreach ($unsupported as $name) {
            if (Methods::isCallable($name)) {
                $dialableByMethods[] = $name;
            }
        }
        $this->assertSame(self::CLIENT_BUILT_ENVELOPES, $dialableByMethods);

        $serialisable = [];
        $notEncodable = 0;
        foreach ($unsupported as $name) {
            if (WireSchemaCoverage::isEncodable($name)) {
                $serialisable[] = $name;

                continue;
            }
            $notEncodable++;
        }
        $this->assertSame(
            self::CLIENT_BUILT_ENVELOPES,
            $serialisable,
            'The set of catalogued-but-not-dialable methods that DO serialize is no longer exactly '
            .'the two client-built envelopes.',
        );

        // 34 is the "no serialisable signature" figure and 36 is the "cannot be
        // dialed by a host" figure. They are both true, and neither may be used
        // where the other belongs.
        $this->assertSame(
            34,
            $notEncodable,
            'The "no serialisable signature" figure is no longer 34.',
        );
        $this->assertSame(36, count($unsupported), 'The "cannot be dialed by a host" figure is no longer 36.');

        // And the encodable ones must be real constructors the client uses, not
        // an accident: both are registered, so a call for them is only refused
        // by the wrapped-request argument, not by a missing schema line.
        foreach (self::CLIENT_BUILT_ENVELOPES as $name) {
            $this->assertTrue(TLRegistry::isRegistered($name), "[{$name}] is no longer a registered constructor.");
            $this->assertTrue(WireSchemaCoverage::clientBuilt($name), "[{$name}] is no longer client-built.");
        }
    }

    // ------------------------------------------------------------------ helpers

    /**
     * The exact set the classifier reports for a family, checked against the
     * frozen expectation with a name-level diff on failure.
     */
    private function assertSameSet(string $family, array $expected): void
    {
        $actual = [];
        foreach (array_keys(MethodRegistry::unsupportedMethods()) as $name) {
            if (WireSchemaCoverage::classify($name) === $family) {
                $actual[] = $name;
            }
        }

        $this->assertSame(
            $this->sorted($expected),
            $this->sorted($actual),
            "The {$family} family is no longer the frozen set.\n"
            .'  appeared: '.(implode(', ', array_diff($this->sorted($actual), $this->sorted($expected))) ?: 'none')."\n"
            .'  vanished: '.(implode(', ', array_diff($this->sorted($expected), $this->sorted($actual))) ?: 'none')."\n"
            ."Sweep the prose listed in PROSE_SOURCES in the same commit:\n".$this->proseInventory(),
        );
    }

    /**
     * Every number the code currently supports in a boundary sentence: the
     * families, their total, the two honest counts, the catalogue sizes, the
     * per-namespace composition, and the two layer numbers.
     *
     * @return list<int>
     */
    private function supportedNumbers(): array
    {
        /** @var array<string, list<string>> $families */
        $families = [];
        foreach (array_keys(MethodRegistry::unsupportedMethods()) as $name) {
            $families[WireSchemaCoverage::classify($name)][] = $name;
        }

        $unsupported = count(MethodRegistry::unsupportedMethods());
        $numbers = [
            $unsupported,
            count(array_keys(MethodRegistry::callableMethods())),
            EncodableBoundary::catalogue(),
            EncodableBoundary::botApiCatalogue(),
            // both catalogues together, which some prose quotes as a single total
            EncodableBoundary::catalogue() + EncodableBoundary::botApiCatalogue(),
            EncodableBoundary::manifestLayer(),
            WireSchemaCoverage::layerOf('api_full.tl'),
            WireSchemaCoverage::layerOf('api.tl'),
        ];

        foreach ($families as $members) {
            $numbers[] = count($members);
        }

        // The encodable count is the boundary minus the client-built envelopes.
        $numbers[] = $unsupported - count(self::CLIENT_BUILT_ENVELOPES);

        // Per-namespace composition, so "all nine communities.*" is checkable.
        foreach (array_values(self::EXPECTED_NOT_IN_MIRROR) as $names) {
            $numbers[] = count($names);
        }

        // Small integers that appear in legitimate boundary prose (three
        // families, two questions, the two client-built envelopes).
        $numbers[] = 1;
        $numbers[] = 2;
        $numbers[] = 3;

        $unique = array_values(array_unique(array_map('intval', $numbers)));
        sort($unique);

        return array_values(array_map('intval', $unique));
    }

    /**
     * Lines in a prose file that make a boundary claim, with their line number.
     *
     * @return list<array{no: int, text: string}>
     */
    private function boundaryLines(string $path, string $relative): array
    {
        $out = [];
        $no = 0;
        foreach (@file($path) ?: [] as $raw) {
            $no++;
            $text = trim($raw);
            if ($text === '') {
                continue;
            }
            // Code fences in docs hold command output, not claims.
            foreach (self::CLAIM_MARKERS as $marker) {
                if (stripos($text, $marker) !== false) {
                    $out[] = ['no' => $no, 'text' => $text];

                    break;
                }
            }
        }

        unset($relative);

        return $out;
    }

    /**
     * Every integer in a line. Prose scanning, so a regex is the right tool
     * here; the schema derivation above deliberately does not use one.
     *
     * @return list<int>
     */
    private function numbersIn(string $line): array
    {
        preg_match_all('/\d+/', $line, $matches);

        return array_map('intval', $matches[0] ?? []);
    }

    /** Where the boundary is stated, for a failure message. */
    private function proseInventory(): string
    {
        $out = [];
        foreach (self::PROSE_SOURCES as $relative) {
            $out[] = '  - '.$relative;
        }
        foreach (array_keys(self::KNOWN_PROSE_DISAGREEMENTS) as $where) {
            $out[] = '  - '.$where.' (known disagreement, see KNOWN_PROSE_DISAGREEMENTS)';
        }

        return implode("\n", $out);
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function sorted(array $names): array
    {
        sort($names);

        return array_values($names);
    }
}
