<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Laravel\Notify\ExceptionRedactor;
use PHPUnit\Framework\TestCase;

/**
 * The redactor is the security surface of exception reporting: its whole job
 * is to decide what does NOT reach a chat. Every case below is a string that
 * must come out changed, or text that must come out intact — the second kind
 * matters as much, because a redactor that eats the whole message leaves the
 * operator with nothing.
 *
 * String-function only (strspn/strcspn/str_replace), matching the zero-regex
 * discipline of the rest of the package.
 */
final class ExceptionRedactorTest extends TestCase
{
    private const SESSION = '1xAbCdEfGhIjKlMnOpQrStUvWxYz0123456789-_AbCdEfGhIjKlMnOpQrStUvWxYz01234';

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

    private const BOT_TOKEN = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw';

    private ExceptionRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new ExceptionRedactor;
    }

    public function test_it_redacts_a_session_string_keyed_by_name(): void
    {
        $out = $this->redactor->redact('session='.self::SESSION);

        self::assertStringNotContainsString(self::SESSION, $out);
        self::assertStringContainsString('[redacted]', $out);
    }

    public function test_it_redacts_a_bare_session_string_with_no_key(): void
    {
        $out = $this->redactor->redact('auth failed for '.self::SESSION.' on dc2');

        self::assertStringNotContainsString(self::SESSION, $out);
        self::assertStringContainsString('auth failed for', $out);
        self::assertStringContainsString('on dc2', $out);
    }

    public function test_it_redacts_a_hex_shaped_secret(): void
    {
        $hex = str_repeat('a7f3c91e', 6).'ab';   // 50 hex chars

        $out = $this->redactor->redact('dc id 2 auth '.$hex.' rejected');

        self::assertStringNotContainsString($hex, $out);
    }

    public function test_it_redacts_a_bot_token_either_side_of_its_colon(): void
    {
        foreach (['bot_token='.self::BOT_TOKEN, 'token: '.self::BOT_TOKEN, 'using '.self::BOT_TOKEN] as $input) {
            $out = $this->redactor->redact($input);

            self::assertStringNotContainsString('AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw', $out);
            self::assertStringContainsString('[redacted]', $out);
        }
    }

    public function test_it_redacts_a_fragmented_credential_across_whitespace(): void
    {
        // bin/scan-credentials.php joins runs split on whitespace, quotes,
        // commas and '=' — a dump of that shape must not survive either.
        $chunks = str_split(self::SESSION, 20);
        $input = 'auth_key='.implode("\n", $chunks);

        $out = $this->redactor->redact($input);

        self::assertStringNotContainsString(self::SESSION, $out);
        foreach ($chunks as $chunk) {
            self::assertStringNotContainsString($chunk, $out);
        }
    }

    public function test_it_redacts_a_credential_wrapped_in_quotes(): void
    {
        $out = $this->redactor->redact('{"auth_key":"'.self::authKey().'","dc":2}');

        self::assertStringNotContainsString(self::authKey(), $out);
        self::assertStringContainsString('dc', $out);
    }

    public function test_it_leaves_ordinary_text_untouched(): void
    {
        $input = 'SQLSTATE[42S02]: Base table or view not found: 1146 Table \'teleframe.tg_messages\' doesn\'t exist';

        self::assertSame($input, $this->redactor->redact($input));
    }

    public function test_it_leaves_ordinary_class_names_and_paths_untouched(): void
    {
        $input = 'MeRezaRezaei\\Teleframe\\Laravel\\Notify\\ExceptionReporter in /home/me/app/src/Handler.php';

        self::assertSame($input, $this->redactor->redact($input));
    }

    public function test_it_leaves_a_long_prose_sentence_untouched(): void
    {
        $input = 'the connection was reset by peer while the encrypted frame pump was mid-handshake and retried';

        self::assertSame($input, $this->redactor->redact($input));
    }

    public function test_it_bounds_the_length(): void
    {
        $out = $this->redactor->redact(str_repeat('x', 5000), 120);

        self::assertLessThanOrEqual(120, strlen($out));
    }

    public function test_it_handles_an_empty_string(): void
    {
        self::assertSame('', $this->redactor->redact(''));
    }
}
