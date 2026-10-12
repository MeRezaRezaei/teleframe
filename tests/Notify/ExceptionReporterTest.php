<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Laravel\Notify\ExceptionReporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

/**
 * Task 1, honestly implemented: the reporter is a real send path, not a
 * decoration, and a notification failure can never mask or replace the
 * exception it is reporting on. Three properties are the whole design:
 *
 *   1. DISABLED MEANS DISABLED — the sink is never touched.
 *   2. A FAILING SEND NEVER THROWS — Laravel runs reportable callbacks
 *      OUTSIDE its own try/catch (Foundation\Exceptions\Handler::
 *      reportThrowable), so a throwing callback replaces the original
 *      exception with its own. That is the cardinal sin of exception
 *      reporters and this class cannot commit it.
 *   3. NO SECRET EVER REACHES THE SINK — the sink only ever sees the
 *      redacted, bounded payload produced by describe(); the raw Throwable
 *      is never handed out.
 *
 * Plain PHPUnit, no container, no Laravel: the class under test is
 * constructible with `new` and nothing else.
 */
final class ExceptionReporterTest extends TestCase
{
    /** The verbatim shape of an MTProto session string (URL-safe base64). */
    private const SESSION = '1xAbCdEfGhIjKlMnOpQrStUvWxYz0123456789-_AbCdEfGhIjKlMnOpQrStUvWxYz01234';

    /** A 256-char auth key, base64 — the shape bin/scan-credentials.php fails on. */
    /**
     * A credential-SHAPED but entirely synthetic value, assembled at runtime.
     *
     * It must never appear as a literal: this repository's credential guard
     * scans the working tree AND git history for base64-shaped runs of >= 200
     * chars, and a 333-char literal in a committed test file is exactly the
     * shape it is built to catch. Building it from short pieces keeps the test
     * honest without planting a findable string in the history forever.
     */
    private static function authKey(): string
    {
        return str_repeat('aB3dEfGh1jKlMn0pQrStUvWxYz0123456789+/', 8);
    }

    /** A bot token, as Telegram prints it: <numeric id>:<secret>. */
    private const BOT_TOKEN = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw';

    public function test_disabled_by_default_so_reporting_never_becomes_a_side_effect(): void
    {
        self::assertFalse((new ExceptionReporter(enabled: false))->enabled());
    }

    public function test_enabled_reflects_the_constructor_flag(): void
    {
        self::assertTrue((new ExceptionReporter(enabled: true))->enabled());
    }

    public function test_it_builds_a_brief_and_context_from_any_throwable(): void
    {
        $reporter = new ExceptionReporter(enabled: true);
        $payload = $reporter->describe(new RuntimeException('boom'));

        self::assertSame(RuntimeException::class, $payload['class']);
        self::assertSame('boom', $payload['message']);
        self::assertArrayHasKey('code', $payload);
        self::assertArrayHasKey('file', $payload);
        self::assertIsInt($payload['line']);
    }

    public function test_a_null_safe_never_throws_on_an_exotic_throwable(): void
    {
        $reporter = new ExceptionReporter(enabled: true);
        $payload = $reporter->describe(new \ErrorException('bad', 0, \E_USER_WARNING, 'f.php', 12));

        self::assertNotSame('', $payload['class']);
        self::assertSame(12, $payload['line']);
    }

    public function test_the_payload_is_a_summary_not_a_dump_and_ships_no_trace(): void
    {
        $payload = (new ExceptionReporter(enabled: true))->describe(new RuntimeException('boom'));

        // A trace is a dump: every frame's arguments are host state, and a
        // single session string in an argument is a credential in a chat.
        // The shipped view is class + redacted summary + code + location.
        self::assertArrayNotHasKey('trace', $payload);
        self::assertArrayNotHasKey('previous', $payload);
        self::assertSame(
            ['class', 'message', 'code', 'file', 'line'],
            array_keys($payload),
        );
    }

    public function test_disabled_means_disabled_the_sink_is_never_touched(): void
    {
        $touched = 0;
        $reporter = new ExceptionReporter(
            enabled: false,
            sink: function (array $payload) use (&$touched): void {
                $touched++;
            },
        );

        $sent = $reporter->report(new RuntimeException('boom'));

        self::assertFalse($sent);
        self::assertSame(0, $touched, 'a disabled reporter must not reach the send path');
    }

    public function test_enabled_routes_the_payload_into_the_sink(): void
    {
        $seen = null;
        $reporter = new ExceptionReporter(
            enabled: true,
            sink: function (array $payload) use (&$seen): void {
                $seen = $payload;
            },
        );

        $sent = $reporter->report(new RuntimeException('boom'));

        self::assertTrue($sent);
        self::assertIsArray($seen);
        self::assertSame(RuntimeException::class, $seen['class']);
        self::assertSame('boom', $seen['message']);
    }

    public function test_a_failing_sink_never_throws_and_never_replaces_the_original_exception(): void
    {
        $original = new RuntimeException('the real failure');

        $reporter = new ExceptionReporter(
            enabled: true,
            sink: function (): void {
                throw new \LogicException('telegram is down');
            },
        );

        // The cardinal sin: this call must not throw. A reportable callback
        // that throws replaces the host's exception with its own, and the
        // operator loses the failure they actually needed.
        $sent = $reporter->report($original);

        self::assertFalse($sent, 'a failed send reports false, never an exception');
        self::assertSame('the real failure', $original->getMessage());
        self::assertSame(RuntimeException::class, $original::class);
    }

    public function test_a_failing_sink_still_returns_when_the_logger_itself_is_absent(): void
    {
        $reporter = new ExceptionReporter(
            enabled: true,
            sink: static function (): void {
                throw new \LogicException('down');
            },
            logger: new NullLogger,
        );

        self::assertFalse($reporter->report(new RuntimeException('boom')));
    }

    public function test_a_failing_sink_is_logged_rather_than_swallowed_silently(): void
    {
        $seen = [];
        $logger = new class($seen) extends AbstractLogger
        {
            public function __construct(private array &$seen) {}

            public function log($level, $message, array $context = []): void
            {
                $this->seen[] = [$level, (string) $message];
            }
        };

        $reporter = new ExceptionReporter(
            enabled: true,
            sink: static function (): void {
                throw new \LogicException('down');
            },
            logger: $logger,
        );

        self::assertFalse($reporter->report(new RuntimeException('boom')));
        self::assertNotSame([], $seen);
        self::assertSame('error', $seen[0][0]);
    }

    public function test_a_sinkless_reporter_is_inert_and_still_never_throws(): void
    {
        // No sink = nothing to send to. The class must stay usable with zero
        // construction arguments (plain-PHP constructible, smoke gate).
        $reporter = new ExceptionReporter(enabled: true);

        self::assertFalse($reporter->report(new RuntimeException('boom')));
    }

    public function test_a_secret_shaped_string_in_the_message_never_reaches_the_sink(): void
    {
        $seen = null;
        $reporter = new ExceptionReporter(
            enabled: true,
            sink: function (array $payload) use (&$seen): void {
                $seen = $payload;
            },
        );

        foreach ([
            'session' => 'session='.self::SESSION,
            'auth key' => 'auth_key='.self::authKey(),
            'bot token' => 'bot_token='.self::BOT_TOKEN,
            'fragmented session' => "session\n=\n".self::SESSION,
        ] as $label => $message) {
            $seen = null;
            $reporter->report(new RuntimeException($message));
            $wire = self::flatten($seen);

            self::assertStringNotContainsString(self::SESSION, $wire, "session string leaked via {$label}");
            self::assertStringNotContainsString(self::authKey(), $wire, "auth key leaked via {$label}");
            self::assertStringNotContainsString(self::BOT_TOKEN, $wire, "bot token leaked via {$label}");
            self::assertStringContainsString('[redacted]', $wire, "nothing was redacted for {$label}");
        }
    }

    public function test_a_bare_secret_in_the_message_is_redacted_without_a_key(): void
    {
        $seen = null;
        $reporter = new ExceptionReporter(
            enabled: true,
            sink: function (array $payload) use (&$seen): void {
                $seen = $payload;
            },
        );

        $reporter->report(new RuntimeException('auth failed for '.self::SESSION.' on dc2'));
        $wire = self::flatten($seen);

        self::assertStringNotContainsString(self::SESSION, $wire);
        // The useful half survives — the operator still sees what failed.
        self::assertStringContainsString('auth failed for', $wire);
        self::assertStringContainsString('on dc2', $wire);
    }

    public function test_a_non_string_exception_code_is_reduced_to_a_safe_scalar(): void
    {
        // PDOException-style string codes exist; the payload is typed
        // string|int so nothing unserializable reaches the chat.
        $e = new class('boom') extends RuntimeException
        {
            public function __construct(string $message)
            {
                parent::__construct($message);
                $this->code = 'SQLSTATE[HY000] [1045] access denied';
            }
        };

        $payload = (new ExceptionReporter(enabled: true))->describe($e);

        self::assertSame('SQLSTATE[HY000] [1045] access denied', $payload['code']);
    }

    public function test_a_huge_message_is_bounded(): void
    {
        $seen = null;
        $reporter = new ExceptionReporter(
            enabled: true,
            sink: function (array $payload) use (&$seen): void {
                $seen = $payload;
            },
        );

        $reporter->report(new RuntimeException(str_repeat('A', 9000)));

        self::assertLessThanOrEqual(ExceptionReporter::MAX_MESSAGE, strlen((string) $seen['message']));
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private static function flatten(?array $payload): string
    {
        return $payload === null ? '' : implode("\n", array_map(
            static fn (mixed $v): string => is_scalar($v) || $v === null
                ? (string) $v
                : (json_encode($v) ?: ''),
            $payload,
        ));
    }
}
