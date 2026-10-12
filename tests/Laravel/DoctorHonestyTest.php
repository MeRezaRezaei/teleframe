<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Tests;

use Illuminate\Console\OutputStyle;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\AuthKeyException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Laravel\Console\DoctorCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * teleframe:doctor must not report a healthy install for a broken one.
 *
 * Every probe is driven through a public seam with injected doubles: no app
 * container, no Redis server, no Telegram, no credentials.
 */
class DoctorHonestyTest extends TestCase
{
    // ---- defect 1: a stored session must actually be probed ---------------

    /**
     * The default (no --account) path is unchanged where it matters: the
     * throwaway-key probe still labels itself 'handshake+getNearestDc' and
     * still fails on a dead host.
     *
     * The bus probe's exit code DID change, and deliberately. With no container
     * the probe is skipped, and a skipped probe used to return 0 — so the
     * package's own health check reported the bus healthy on a host where
     * nothing about the bus had been checked. This test pinned that, by the
     * same accident as the webhook one: it asserted the old behaviour because
     * that was what the code did. Skipped is not passed, and the exit code now
     * says so.
     */
    public function test_default_path_is_unchanged_without_an_account(): void
    {
        [$cmd, $buffer] = $this->doctor();
        $client = $this->deadHostClient();

        $exit = $cmd->probeConnectivity($client, '127.0.0.1', 1);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('FAIL handshake+getNearestDc 127.0.0.1:1', $buffer->fetch());
        $this->assertSame(
            1,
            $cmd->probeBusIfConfigured(),
            'a bus probe that never ran must not report the bus healthy',
        );
    }

    /**
     * The stored session string is imported and its auth key is carried into
     * the client. A blank key is what made ensureAuthKey() silently run a
     * fresh handshake and hide a revoked session.
     */
    public function test_stored_session_is_imported_not_replaced_by_a_throwaway_key(): void
    {
        [$cmd] = $this->doctor();

        $stored = (new SessionData(dcId: 4, authKey: str_repeat('k', 256), userId: 42))->exportString();
        $client = $cmd->clientForStoredSession($stored, 12345, 'hash');

        $this->assertNotNull($client->session);
        $this->assertSame(256, strlen((string) $client->session->authKey), 'the STORED auth key must be used');
        $this->assertSame(4, $client->session->dcId, "the stored session's own DC is authoritative");
        $this->assertSame(42, $client->session->userId);
    }

    /** A stored session with no real key is refused loudly, not handshaken. */
    public function test_stored_session_without_an_auth_key_is_refused(): void
    {
        [$cmd] = $this->doctor();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('256-byte auth key');

        $cmd->clientForStoredSession((new SessionData(dcId: 2, authKey: 'short'))->exportString(), 1, 'h');
    }

    /**
     * A revoked/terminated session surfaces by name and NEVER as an OK line.
     */
    public function test_revoked_stored_session_is_reported_by_name_and_never_as_success(): void
    {
        [$cmd, $buffer] = $this->doctor();

        $client = $this->createStub(Client::class);
        $client->method('callToHost')->willThrowException(new AuthKeyException('SESSION_REVOKED'));

        $exit = $cmd->probeConnectivity($client, '149.154.167.91', 443, 'stored session account=7 dc=4');
        $out = $buffer->fetch();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('SESSION_REVOKED', $out, 'the real auth failure must be named');
        $this->assertStringNotContainsString('OK ', $out, 'no success line may be printed for a revoked session');
    }

    // ---- defect 2: the bus probe -----------------------------------------

    public function test_bus_probe_reports_the_consumer_group_as_present(): void
    {
        [$cmd, $buffer] = $this->doctor();
        $redis = $this->redisDouble(groups: [StreamSchema::GROUP], deadLetter: 0);

        $exit = $cmd->probeBus($redis, 5.0);
        $out = $buffer->fetch();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('OK bus PING', $out);
        $this->assertStringContainsString('XLEN '.StreamSchema::STREAM, $out);
        $this->assertStringContainsString('consumer group '.StreamSchema::GROUP.' present', $out);
        $this->assertStringNotContainsString('FINDING', $out);
    }

    /**
     * The absent group is the bug that starved teleframe:ingest. It must be
     * named, and it is a FINDING (exit 0), not a hard failure.
     */
    public function test_bus_probe_names_an_absent_consumer_group_as_a_finding(): void
    {
        [$cmd, $buffer] = $this->doctor();
        $redis = $this->redisDouble(groups: ['some-other-consumer'], deadLetter: 7);

        $exit = $cmd->probeBus($redis, 5.0);
        $out = $buffer->fetch();

        $this->assertSame(0, $exit, 'a missing group is a finding, not a hard failure');
        $this->assertStringContainsString('FINDING', $out);
        $this->assertStringContainsString(StreamSchema::GROUP, $out);
        $this->assertStringContainsString('ABSENT', $out);
        $this->assertStringContainsString('XLEN '.StreamSchema::DL.' = 7', $out, 'dead-letter size is reported');
    }

    /** A stream Redis knows nothing about must not read as healthy. */
    public function test_bus_probe_surfaces_a_failing_xinfo(): void
    {
        [$cmd, $buffer] = $this->doctor();
        $redis = $this->createStub(RedisConnectionContract::class);
        $redis->method('llen')->willReturn(0);
        $redis->method('command')->willReturnCallback(static function (string $command, array $args = []): mixed {
            if (strtolower($command) === 'xinfo') {
                throw new \RuntimeException('no such key');
            }

            return 'PONG';
        });

        $exit = $cmd->probeBus($redis, 5.0);
        $out = $buffer->fetch();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('NOT CONFIRMED', $out);
        $this->assertStringContainsString(StreamSchema::GROUP, $out);
    }

    /**
     * An unreachable/hung bus must NOT read as healthy.
     *
     * The reporting path is asserted DETERMINISTICALLY: the connection's
     * command throws, exactly as it would after `bounded()`'s alarm has cut a
     * hung call. Relying on the alarm actually firing mid-sleep made this test
     * pass locally and fail in CI on both PHP 8.3 and 8.4 — a wall-clock race
     * is not evidence, and a flaky gate trains you to ignore red.
     */
    public function test_an_unreachable_bus_is_reported_as_a_failure_not_as_healthy(): void
    {
        [$cmd, $buffer] = $this->doctor();
        $redis = $this->createStub(RedisConnectionContract::class);
        $redis->method('command')->willThrowException(new RuntimeException('Connection refused'));
        $redis->method('llen')->willReturn(0);

        $exit = $cmd->probeBus($redis, 1.0);
        $out = $buffer->fetch();

        $this->assertSame(1, $exit, 'an unreachable bus must not be reported as healthy');
        $this->assertStringContainsString('FAIL bus PING', $out);
        $this->assertStringContainsString('Connection refused', $out);
    }

    /**
     * The alarm bound itself: `bounded()` must abandon an op that overruns it.
     *
     * This is the one place a clock is unavoidable, so it is bounded on purpose
     * — a 1s alarm against a 5s block leaves a 5x margin, and the assertion is
     * only that the op was CUT (it returned before the block could finish on
     * its own), never that a specific millisecond elapsed. Where pcntl is
     * unavailable `bounded()` degrades to calling the op directly by design, so
     * the test says so instead of asserting a guarantee the code does not make.
     */
    public function test_the_bus_probe_is_abandoned_rather_than_waited_out(): void
    {
        if (! function_exists('pcntl_alarm') || ! function_exists('pcntl_signal')) {
            self::markTestSkipped('pcntl unavailable — bounded() degrades to an unbounded op by design');
        }

        [$cmd, $buffer] = $this->doctor();
        $redis = $this->createStub(RedisConnectionContract::class);
        $redis->method('command')->willReturnCallback(static function (): string {
            usleep(5_000_000);

            return 'PONG';
        });
        $redis->method('llen')->willReturn(0);

        $t0 = microtime(true);
        $exit = $cmd->probeBus($redis, 1.0);
        $elapsed = microtime(true) - $t0;
        $out = $buffer->fetch();

        $this->assertSame(1, $exit, 'a hung bus must not be reported as healthy');
        $this->assertStringContainsString('FAIL bus PING', $out);
        $this->assertStringContainsString('timed out', $out);
        // The 5s block never completed: the alarm cut it. A generous ceiling
        // keeps this honest without pinning the machine's scheduling.
        $this->assertLessThan(4.0, $elapsed, 'the probe must be abandoned, not waited out');
    }

    // ---- doubles ---------------------------------------------------------

    /** @return array{0: DoctorCommand, 1: BufferedOutput} */
    private function doctor(): array
    {
        $buffer = new BufferedOutput;
        $cmd = new DoctorCommand;
        // probeConnectivity()/probeBus() only use line(); a bare command never
        // ran through run(), so inject a real output to capture.
        $cmd->setOutput(new OutputStyle(new ArrayInput([]), $buffer));

        return [$cmd, $buffer];
    }

    private function deadHostClient(): Client
    {
        $client = $this->createStub(Client::class);
        $client->method('callToHost')->willThrowException(new \RuntimeException('Connection refused'));

        return $client;
    }

    /**
     * @param  list<string>  $groups
     */
    private function redisDouble(array $groups, int $deadLetter): RedisConnectionContract
    {
        $redis = $this->createStub(RedisConnectionContract::class);
        $redis->method('llen')->willReturnCallback(static function (string $key) use ($deadLetter): int {
            return $key === StreamSchema::DL ? $deadLetter : 3;
        });
        $redis->method('command')->willReturnCallback(static function (string $command, array $args = []) use ($groups): mixed {
            return match (strtolower($command)) {
                'ping' => 'PONG',
                'xinfo' => array_map(
                    static fn (string $name): array => [
                        'name' => $name,
                        'consumers' => 1,
                        'pending' => 0,
                        'last-delivered-id' => '0-0',
                    ],
                    $groups,
                ),
                default => null,
            };
        });

        return $redis;
    }
}
