<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Notify;

/**
 * ExceptionRedactor — the security surface of exception reporting.
 *
 * Exception text is the ONE string in this package that is, by construction,
 * operator-uncontrolled: a driver error quoting a connection string, a
 * PDOException quoting a DSN, an auth failure quoting the value it rejected.
 * Telegram is a CHAT — a private, read, forwards-and-pastes surface — and
 * this repository's hard rule is that a session string, an auth key, a bot
 * token, a passphrase or a decrypted value never leaves the process.
 * Reporting an exception is therefore a redacting job FIRST and a sending
 * job second: what ships is the exception CLASS, a short redacted summary
 * and the location. Never a dump.
 *
 * Two independent passes, because a credential can arrive either way:
 *
 *   1. KEYED — `auth_key=…`, `"token": …`, `session = …`, in any separator
 *      style. A run whose left-hand side names a credential has its value
 *      replaced outright. A credential FRAGMENTED across whitespace, quotes
 *      or commas — the shape bin/scan-credentials.php joins and fails on —
 *      is covered too: once a key is seen, the runs that follow it are
 *      consumed as fragments until a run that cannot be one ends the value.
 *   2. SHAPED — a bare credential with no key at all. Runs of the base64 /
 *      hex alphabet are judged on shape: mixed case and long, all-hex and
 *      long, or all-digits and long enough to be a card number. Paths, class
 *      names, SQL and prose come out exactly as they went in, because a
 *      redactor that eats the message leaves the operator with nothing.
 *
 * Zero-regex (strspn/strcspn/strpbrk/str_replace vocabulary, matching the
 * rest of the package) and plain-PHP constructible — no container, no config.
 */
final class ExceptionRedactor
{
    /** The one and only replacement token; operators can grep for it. */
    public const REDACTED = '[redacted]';

    /** Default cap on a shipped message: a chat message, not a log file. */
    public const MAX_LENGTH = 240;

    /**
     * Substrings that name a credential on the LEFT of a `=` / `:`. Matched
     * case-insensitively as substrings, so `authKey`, `auth-key` and
     * `auth_key` are all caught.
     */
    private const SECRET_KEYS = [
        'session', 'authkey', 'auth_key', 'auth-key', 'auth key',
        'token', 'password', 'passwd', 'passphrase', 'secret',
        'apikey', 'api_key', 'api-key', 'private_key', 'privatekey',
        'dc_password', 'salt', 'credential', 'signature',
    ];

    /** Characters that END a run — whitespace, quotes, braces, `\`, `/`… */
    private const BREAK_CHARS = " \t\r\n'\"{}[]()<>,;|&?*%$#@^~`\\/:=.";

    /** Mixed-case base64 below this length is prose, not a credential. */
    private const MIXED_MIN = 32;

    /** Hex digest below this length is a short id, not a key. */
    private const HEX_MIN = 32;

    /** A run of pure digits at or above this length is not one we want. */
    private const DIGITS_MIN = 16;

    /** A pending value ends at the first run with no digits and few chars. */
    private const FRAGMENT_MAX = 8;

    public function __construct(private readonly int $maxLength = self::MAX_LENGTH) {}

    /**
     * The single entry point: any text going onto the wire comes through
     * here. Always returns a string; never throws.
     */
    public function redact(string $value, ?int $maxLength = null): string
    {
        if ($value === '') {
            return '';
        }

        $out = '';
        $length = strlen($value);
        $i = 0;
        // True once a credential key has been seen: the runs that follow are
        // a value, possibly split across separators, until one cannot be.
        $pending = false;

        while ($i < $length) {
            $gap = strspn($value, self::BREAK_CHARS, $i);
            if ($gap > 0) {
                $out .= substr($value, $i, $gap);
                $i += $gap;
            }

            if ($i >= $length) {
                break;
            }

            $run = strcspn($value, self::BREAK_CHARS, $i);
            $token = substr($value, $i, $run);
            $i += $run;

            $verdict = $this->maskToken($token, $pending);
            $out .= $verdict['text'];
            $pending = $verdict['pending'];
        }

        $limit = $maxLength ?? $this->maxLength;

        if ($limit > 0 && strlen($out) > $limit) {
            $out = $limit <= 3
                ? substr($out, 0, $limit)
                : rtrim(substr($out, 0, $limit - 3)).'...';
        }

        return $out;
    }

    /**
     * One run's verdict: the text to emit, and whether a credential value is
     * still open for the runs that follow.
     *
     * @return array{text: string, pending: bool}
     */
    private function maskToken(string $token, bool $pending): array
    {
        if ($token === '') {
            return ['text' => '', 'pending' => $pending];
        }

        $separatorAt = $this->separatorAt($token);
        $left = $separatorAt === null ? $token : substr($token, 0, $separatorAt);
        $right = $separatorAt === null ? '' : substr($token, $separatorAt + 1);

        // (1) key=value, in any casing and any separator style.
        if ($left !== '' && $right !== '' && $this->namesSecret($left)) {
            return ['text' => $left.substr($token, $separatorAt, 1).self::REDACTED, 'pending' => true];
        }

        // (1a) a bare key with the value on the NEXT run — `auth_key` `=`
        // `chunk1` `chunk2`. The credential is split across separators, so
        // the key is emitted and the value is left open.
        if ($right === '' && $this->namesSecret($token)) {
            return ['text' => $token, 'pending' => true];
        }

        // (1b) a value split across separators: the chunk after a key.
        $value = $separatorAt === null ? $token : $right;

        if ($pending && $value !== '' && $this->looksLikeFragment($value)) {
            return ['text' => self::REDACTED, 'pending' => true];
        }

        // (2) a bare credential, no key in sight.
        if ($this->looksCredentialShaped($token)) {
            return ['text' => self::REDACTED, 'pending' => $pending];
        }

        return ['text' => $token, 'pending' => false];
    }

    /**
     * The offset of the first `=` or `:` in a run, or null. Callers only ask
     * about runs that do not START with a separator, so a JSON punctuation
     * colon never reads as an empty key.
     */
    private function separatorAt(string $token): ?int
    {
        $eq = strpos($token, '=');
        $colon = strpos($token, ':');

        if ($eq === false) {
            return $colon === false ? null : $colon;
        }

        if ($colon === false) {
            return $eq;
        }

        return min($eq, $colon);
    }

    private function namesSecret(string $left): bool
    {
        $needle = strtolower($left);

        foreach (self::SECRET_KEYS as $key) {
            if (str_contains($needle, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A continuation chunk of a value that a previous run opened: it carries
     * base64/hex characters and, usually, a digit. Ordinary English words
     * carry neither, which is what closes the value again.
     */
    private function looksLikeFragment(string $value): bool
    {
        $length = strlen($value);

        return strpbrk($value, '0123456789') !== false || $length >= self::FRAGMENT_MAX;
    }

    /**
     * A bare credential: the base64/hex alphabet, long, and shaped like a
     * secret rather than like English. Three shapes, because credentials
     * come in three flavours — mixed case (base64, the session-string and
     * auth-key shape, and the secret half of a bot token), pure hex (a
     * digest or a hex key), and a long digit run.
     *
     * `SQLSTATE`, `teleframe`, `RuntimeException` and a file path fail all
     * three, which is what keeps ordinary exception text readable.
     */
    private function looksCredentialShaped(string $token): bool
    {
        $length = strlen($token);

        if ($length < self::MIXED_MIN) {
            return false;
        }

        // `+`, `=`, `-` and `_` may be padding/separators INSIDE a
        // credential, so they are stripped before the alphabet is judged.
        $bare = str_replace(['+', '=', '-', '_'], '', $token);

        if (ctype_xdigit($bare)) {
            return strlen($bare) >= self::HEX_MIN;
        }

        if (ctype_digit($bare)) {
            return strlen($bare) >= self::DIGITS_MIN;
        }

        $hasLower = strpbrk($bare, 'abcdefghijklmnopqrstuvwxyz') !== false;
        $hasUpper = strpbrk($bare, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ') !== false;

        return $hasLower && $hasUpper;
    }
}
