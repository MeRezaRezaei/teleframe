<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Live;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsLiveTelegram;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE PATHS THAT ACTUALLY DIAL — `Client::live()` and `callToHost()`.
 *
 * ## WHY THIS FILE IS THE HONEST COUNTERPART TO A SKIPPING TEST
 *
 * `tests/Core/MTProto/ClientOfflineContractTest.php` contains
 * `test_live_mode_still_reaches_the_wire_for_a_valid_method`, which builds a
 * live client with a RANDOM auth key, calls a valid method, and then:
 *
 * ```php
 * try {
 *     $client->call('help.getNearestDc');
 *     $this->markTestSkipped('a live connection to Telegram was actually established');
 * } catch (\RuntimeException $e) {
 *     $this->assertDoesNotMatchRegularExpression('/wire mirror|ENVELOPE/', $e->getMessage());
 * }
 * ```
 *
 * Read that carefully: **if the connection succeeds, the test SKIPS.** Its
 * only assertion is that a connection FAILURE is not a schema refusal — which
 * is a claim about the failure path and says nothing at all about whether
 * Telegram accepts the wire format this package produces. It is, for the
 * purpose v2 is gated on, backwards: the interesting outcome is the success,
 * and the test treats the success as the reason to run away.
 *
 * **Decision: SUPERSEDED, not deleted.** The old test is not wrong about what
 * it asserts — "a transport failure is not a schema refusal" is a real and
 * small property — but it cannot see the thing this slice exists to measure, and
 * it is reachable by the one outcome that matters. It is left in place,
 * untouched, because it is a genuine narrow contract that runs in every CI pass
 * with no account; deleting it would delete CI coverage to make a point. What is
 * added here is the half it structurally cannot have: a run that REQUIRES the
 * connection to succeed, treats success as the assertion, and is unreachable
 * without an explicit opt-in.
 *
 * ## THE THREE STEPS, AND WHAT EACH ONE COSTS
 *
 *   `loopback_dial` — dials 127.0.0.1 on the discard port. No account, no
 *     Telegram, no credential. Proves the dial path RUNS and refuses, and it is
 *     the one step that can be demonstrated by anyone.
 *   `wire_read`   — `Client::live()` + `call()` against the real DC with the
 *     funded account's session. Two `LIVE_SAFE` reads.
 *   `dc_dial`     — `callToHost()` against `149.154.x.x:443`, the test-only seam
 *     the doctor command uses (`src/Laravel/Console/DoctorCommand.php:162`).
 *
 * Every assertion here is ABOUT THE ANSWER, not about the attempt. A refused
 * dial is a failure of this gate, never a pass.
 */
final class LiveWireGateTest extends TestCase
{
    use RunsLiveTelegram;

    /**
     * THE STEP THAT NEEDS NOTHING.
     *
     * A real `stream_socket_client` against a closed loopback port: the dial
     * path genuinely executes and genuinely refuses, and nothing leaves the
     * machine. This is the proof that an ARMED run executes rather than skipping
     * — it needs no funded account, only the opt-in.
     */
    #[Test]
    public function an_armed_loopback_dial_really_dials_and_really_refuses(): void
    {
        $this->requireLiveStep('loopback_dial');

        [$host, $port] = self::liveLoopbackDialTarget();

        // A random 256-byte key, so the client skips the DH handshake and goes
        // straight to the connect — which is the path under test. It is not a
        // credential and authenticates nothing.
        $client = new Client(
            apiId: 1,
            apiHash: 'h',
            session: new SessionData(dcId: 2, authKey: random_bytes(256)),
            live: true,
        );

        $thrown = null;
        try {
            $client->callToHost($host, $port);
        } catch (RuntimeException $e) {
            $thrown = $e;
        } finally {
            $client->close();
        }

        self::assertInstanceOf(
            RuntimeException::class,
            $thrown,
            'the loopback dial MUST fail — if it returned an answer, something is listening on the '
            .'discard port and this step is no longer proving that the dial path runs.',
        );
        self::assertStringContainsString(
            $host,
            $thrown->getMessage(),
            'the refusal must name the endpoint it tried, or an operator cannot tell a network problem '
            .'from a credential problem',
        );
        self::assertNotInstanceOf(
            InvalidArgumentException::class,
            $thrown,
            'a dial failure must not be reported as an encodability refusal — the two are different '
            .'problems and the rest of the live lane tells them apart.',
        );
    }

    /**
     * THE CENTRAL CLAIM.
     *
     * A live read must come back as a real Telegram constructor, and must NOT
     * carry the offline marker. That single distinction is the difference
     * between "the catalogue says this method exists" and "Telegram accepted a
     * packet this package built". The offline suite cannot produce this result
     * by construction, because it never opens a socket.
     */
    #[Test]
    public function a_live_read_answers_with_a_server_constructor_and_not_an_offline_marker(): void
    {
        $this->requireLiveStep('wire_read');

        $client = self::liveUserClient();

        try {
            $answer = $client->call('help.getConfig');

            self::assertArrayNotHasKey(
                '_offline',
                $answer,
                'an _offline marker came back from a LIVE client — nothing was sent, so this run '
                .'proved nothing and must be red',
            );
            self::assertSame(
                'config',
                $answer['_'] ?? null,
                'Telegram answered with something other than the config constructor. The packet was '
                .'accepted only if the answer is the real one; anything else is a wire-format problem.',
            );
        } finally {
            $client->close();
        }
    }

    /**
     * The batch path is a DIFFERENT encoder wrapper (`msg_container`), so a
     * single-call proof does not cover it. `callMany()` also has its own offline
     * stub shape, which means the same "nothing was sent" fraud is available in
     * the batch path and has to be refused the same way.
     */
    #[Test]
    public function a_live_batch_is_not_a_batch_of_offline_markers(): void
    {
        $this->requireLiveStep('wire_read');

        $client = self::liveUserClient();

        try {
            $answers = $client->callMany([
                'config' => ['method' => 'help.getConfig', 'params' => []],
                'nearest' => ['method' => 'help.getNearestDc', 'params' => []],
            ]);

            self::assertSame(['config', 'nearest'], array_keys($answers), 'batch keys and order are part of the contract');

            foreach ($answers as $key => $answer) {
                self::assertArrayNotHasKey(
                    '_offline',
                    $answer,
                    "batch entry \"{$key}\" came back as an offline marker — the batch path never reached Telegram",
                );
                self::assertIsArray($answer);
                self::assertNotSame('', (string) ($answer['_'] ?? ''), "batch entry \"{$key}\" carried no constructor");
            }

            self::assertSame('nearestDc', $answers['nearest']['_'] ?? null);
        } finally {
            $client->close();
        }
    }

    /**
     * `callToHost()` is a SEPARATE code path: it forces a fresh handshake, never
     * caches, and always closes. The doctor command depends on it
     * (`src/Laravel/Console/DoctorCommand.php:162`), so it is the one path in
     * this repository that dials `149.154.x.x:443` directly rather than through
     * the session's DC.
     */
    #[Test]
    public function call_to_host_dials_the_production_dc_and_gets_a_real_nearest_dc(): void
    {
        $this->requireLiveStep('dc_dial');

        $dcId = self::liveDcId();
        $host = Client::DC_IPS[$dcId];

        $client = self::liveUserClient();

        try {
            $answer = $client->callToHost($host, Client::DEFAULT_PORT);

            self::assertArrayNotHasKey('_offline', $answer, 'callToHost must never be answered locally');
            self::assertSame(
                'nearestDc',
                $answer['_'] ?? null,
                "the direct dial to {$host}:".Client::DEFAULT_PORT.' did not return a nearestDc constructor',
            );
            self::assertArrayHasKey(
                'this_dc',
                $answer,
                'a nearestDc answer carries this_dc; without it the constructor was not decoded from the wire',
            );
        } finally {
            $client->close();
        }
    }

    /**
     * THE GATE APPLIES BEFORE THE DIAL, ON EVERY PATH.
     *
     * Cheap to assert and load-bearing: it means the live surface is decided in
     * one place, and a test added tomorrow cannot quietly dial
     * `messages.sendMessage` by forgetting to ask.
     */
    #[Test]
    public function the_policy_gate_refuses_a_method_on_neither_list_before_any_dial(): void
    {
        // Deliberately UNGATED: a pure policy assertion with no socket and no
        // account. It is the standing check that the live surface is decided in
        // exactly one place, so gating it would cost coverage for nothing.

        foreach (['messages.sendMessage', 'account.deleteAccount', 'auth.logOut'] as $method) {
            $thrown = null;
            try {
                self::assertLivePolicyAllows($method);
            } catch (RuntimeException $e) {
                $thrown = $e;
            }

            self::assertInstanceOf(RuntimeException::class, $thrown, "{$method} must be refused at the gate");
            self::assertStringContainsString($method, $thrown->getMessage());
        }
    }
}
