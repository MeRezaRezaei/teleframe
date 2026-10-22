<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Live;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Schema\WireSchemaCoverage;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsLiveTelegram;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE 23 LAYER-GAP METHODS — reported, counted, and deliberately NOT answered.
 *
 * ## WHY THIS FILE RUNS UNCONDITIONALLY
 *
 * It needs no account, opens no socket and contacts nothing: every method here
 * is refused by the client BEFORE any I/O, which is the fact worth pinning. A
 * lane of tests that can only run on the operator's funded account cannot carry
 * that fact, so this file is deliberately NOT behind `requireLiveStep()` — and
 * `LiveGateContractTest` exempts it by name.
 *
 * ## THE HONEST QUESTION, AND WHY THE GATE DOES NOT SETTLE IT
 *
 * These 23 are declared by the Layer 229 catalogue and absent from the Layer 227
 * wire mirror. So the honest question is NOT "are they reachable" — they are
 * provably not, and that part is settled. The question is whether being
 * unreachable is a **defect** or a **decision**, and that is a product call:
 *
 *   - `communities.*` and `ephemeral.*` do not exist at Layer 227 AT ALL. No
 *     server speaking the pinned layer implements them, so there is no live run
 *     that could return "unimplemented method" for them. Nothing is knowable
 *     from traffic.
 *   - `messages.*` and `auth.*FirebasePnv*` DO exist at 227 with different
 *     signatures, so their status is a genuine "the layer moved" signal.
 *
 * A gate that answered "these are fine" would be asserting a product decision
 * it has no evidence for; a gate that answered "these are broken" would be
 * asserting the opposite. **This file therefore reports and refuses to
 * conclude.** It writes the list, grouped, to the report file, and states in the
 * report that the reachable-vs-desired question is open and belongs to the
 * operator.
 *
 * That is the whole reason it is a report and not an assertion: an assertion
 * here would be the gate making a product decision on the operator's behalf,
 * which is exactly the sort of thing a gate must never quietly do.
 */
final class LiveLayerGapReportTest extends TestCase
{
    use RunsLiveTelegram;

    /**
     * The 23, pinned by name.
     *
     * Pinned rather than merely derived so that the DERIVED set and the
     * KNOWN set must agree: if a schema update moves the gap, this file goes red
     * on the commit that moved it instead of silently reporting a new number.
     * The grouping below is the operator-facing one.
     *
     * @var array<string, list<string>>
     */
    private const EXPECTED_BY_NAMESPACE = [
        'auth' => [
            'auth.finishFirebasePnvLogin',
            'auth.firebasePnvSignUp',
            'auth.initFirebasePnvLogin',
        ],
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
        'messages' => [
            'messages.composeRichMessageWithAI',
            'messages.requestChatJoinWebView',
            'messages.translateRichMessage',
        ],
    ];

    #[Test]
    public function the_layer_gap_is_twenty_three_methods_and_the_pinned_list_still_agrees(): void
    {
        $derived = $this->derivedLayerGapMethods();
        $pinned = $this->pinnedLayerGapMethods();

        sort($derived);
        $expected = $pinned;
        sort($expected);

        self::assertCount(23, $derived, 'the layer gap count is the contract this file reports against');
        self::assertSame(
            $expected,
            $derived,
            'the derived layer-gap set and the pinned set disagree — a schema update moved the gap. '
            .'Re-read docs/decisions.md before updating this list, because "unreachable" and "undesired" '
            .'are different findings.',
        );
    }

    /**
     * EVERY one of the 23 is refused pre-I/O, by name, with a reason that names
     * the two layers.
     *
     * The `InvalidArgumentException` / `RuntimeException` split is the load-bearing
     * part. `Client::call()` throws `RuntimeException` for a transport or
     * protocol failure (`src/Core/MTProto/Client.php:299-304`) and
     * `InvalidArgumentException` for an encodability refusal (`assertEncodable()`,
     * `:244`, called before the live/offline branch at `:311`). So asserting the
     * exception TYPE is what proves the refusal happened before a socket could
     * be opened — a client that had dialled and failed would throw the other
     * class entirely.
     */
    #[Test]
    public function every_layer_gap_method_is_refused_before_any_io_and_names_the_two_layers(): void
    {
        foreach ($this->pinnedLayerGapMethods() as $method) {
            // live: TRUE on purpose. If the refusal were not pre-I/O, this
            // client would attempt a real handshake. It must never get that far,
            // and the exception type is the receipt.
            $client = new Client(
                apiId: 1,
                apiHash: 'h',
                session: new SessionData(dcId: 2, authKey: random_bytes(256)),
                live: true,
            );

            try {
                $client->call($method);
                self::fail("{$method} was NOT refused — a layer-gap method encoded and would have been dialled");
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString(
                    $method,
                    $e->getMessage(),
                    "{$method} must be refused BY NAME",
                );
                self::assertStringContainsString(
                    'wire mirror',
                    $e->getMessage(),
                    "{$method} must say it is absent from the wire mirror, not merely that it is unknown",
                );
                self::assertMatchesRegularExpression(
                    '/layer 22[79]/',
                    $e->getMessage(),
                    "{$method} must name the two layers so the refusal is actionable",
                );
            } finally {
                $client->close();
            }
        }
    }

    /** The same refusal offline — no session, no socket, same message. */
    #[Test]
    public function the_layer_gap_refusal_is_identical_offline(): void
    {
        $client = new Client(
            apiId: 1,
            apiHash: 'h',
            session: new SessionData(dcId: 2, authKey: random_bytes(256)),
            live: false,
        );

        foreach ($this->pinnedLayerGapMethods() as $method) {
            try {
                $client->call($method);
                self::fail("{$method} encoded offline, which is a fabricated-result defect");
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString($method, $e->getMessage());
            }
        }

        $client->close();
    }

    /**
     * A transport failure is a DIFFERENT exception class, and that difference is
     * what the test above relies on. Pin it here, so the discrimination cannot
     * quietly stop holding: a change that made transport failures also
     * `InvalidArgumentException` would turn a real proof into a tautology.
     */
    #[Test]
    public function a_transport_failure_is_not_the_same_class_as_a_schema_refusal(): void
    {
        $client = new Client(
            apiId: 1,
            apiHash: 'h',
            session: new SessionData(dcId: 2, authKey: random_bytes(256)),
            live: true,
        );

        // No proxy, no real DC reachable from here in unit conditions — but the
        // point is the CLASS, and this class is what the Client documents for a
        // connect failure. An unknown method, by contrast, is an
        // InvalidArgumentException, asserted in the same test for contrast.
        $transportFailed = false;
        try {
            $client->callToHost('127.0.0.1', 9);
        } catch (RuntimeException) {
            $transportFailed = true;
        } catch (InvalidArgumentException $e) {
            self::fail(
                'a dial failure must not be reported as an encodability refusal; '
                .'the pre-I/O proof in this file depends on those two being different classes. Got: '
                .$e->getMessage(),
            );
        } finally {
            $client->close();
        }

        self::assertTrue(
            $transportFailed,
            'loopback:9 must refuse, otherwise the test host has something listening on the discard port '
            .'and this file is no longer proving what it claims to.',
        );
    }

    /**
     * THE DELIVERABLE: the list, grouped, with the open question stated and NOT
     * answered.
     */
    #[Test]
    public function the_gap_is_reported_grouped_and_the_open_question_is_left_open(): void
    {
        $derived = $this->derivedLayerGapMethods();
        $pinned = $this->pinnedLayerGapMethods();
        sort($derived);

        $total = 0;
        $lines = [];
        $lines[] = 'These methods are declared by the Layer 229 catalogue and absent from the Layer 227 '
            .'wire mirror, so the client refuses them BEFORE any socket is opened. '
            .'That much is settled and needs no account.';
        $lines[] = '';
        $lines[] = '**What this report does NOT say:** whether any of these should be reachable. '
            .'`communities.*` and `ephemeral.*` do not exist at Layer 227 at all, so no live run can '
            .'return "unimplemented method" for them and no amount of traffic can settle it. '
            .'`messages.*` and `auth.*FirebasePnv*` exist at 227 with different signatures, so for those '
            .'a live probe WOULD be informative. Choosing to widen the wire, or to accept the gap, is a '
            .'product decision and this gate deliberately does not make it.';
        $lines[] = '';

        foreach (self::EXPECTED_BY_NAMESPACE as $namespace => $methods) {
            sort($methods);
            $total += count($methods);
            $lines[] = sprintf('### %s.* — %d methods', $namespace, count($methods));
            foreach ($methods as $method) {
                $reason = (string) WireSchemaCoverage::reason($method);
                // The reason is a layer-gap sentence naming both layers; the
                // per-method tail is deliberately collapsed because it is the
                // same sentence 23 times.
                $lines[] = sprintf('- `%s` — refused pre-I/O (%s)', $method, $this->firstClause($reason));
            }
            $lines[] = '';
        }

        $lines[] = sprintf('Total: %d derived, %d pinned, %d expected.', count($derived), count($pinned), $total);
        $lines[] = '';
        $lines[] = 'Zero of these have ever been dialed. That is not a defect of this report — it is '
            .'the reason the report stops here instead of guessing.';

        self::liveAppendReport('layer gap (23 methods, no verdict)', implode("\n", $lines));

        self::assertSame($derived, $pinned, 'the reported list must be the derived list');
    }

    // ── internals ─────────────────────────────────────────────────────────

    /**
     * The set as the SHIPPED ARTIFACTS classify it, not as a fixture says.
     *
     * @return list<string>
     */
    private function derivedLayerGapMethods(): array
    {
        $catalogue = json_decode(
            (string) file_get_contents(__DIR__.'/../../src/Schema/schema/methods-mtproto.json'),
            true,
        );

        self::assertIsArray($catalogue, 'the shipped catalogue must be readable JSON');
        self::assertArrayHasKey('methods', $catalogue);

        $gap = [];
        foreach (array_keys($catalogue['methods']) as $name) {
            if (WireSchemaCoverage::classify((string) $name) === WireSchemaCoverage::NOT_IN_WIRE_MIRROR) {
                $gap[] = (string) $name;
            }
        }

        return $gap;
    }

    /** @return list<string> */
    private function pinnedLayerGapMethods(): array
    {
        $flat = [];
        foreach (self::EXPECTED_BY_NAMESPACE as $methods) {
            foreach ($methods as $method) {
                $flat[] = $method;
            }
        }

        return $flat;
    }

    /** The first sentence of a reason, with the shared boilerplate dropped. */
    private function firstClause(string $reason): string
    {
        $stop = strpos($reason, '. It is a layer-');
        if ($stop === false) {
            $stop = strpos($reason, '.');
        }

        return $stop === false ? $reason : substr($reason, 0, $stop + 1);
    }
}
