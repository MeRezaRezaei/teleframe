<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler\Keyboard;

use InvalidArgumentException;

/**
 * The Q15 callback codec — stateless, replay-scoped, HMAC-signed compact
 * callback tokens. Token shape (zero-regex, colon-split):
 *
 *     v1:<keyId>:<keyIdx>:<arg>:<sig>
 *
 * ``sig`` = base64url of the first 10 raw bytes of
 * ``HMAC-SHA256("keyId:keyIdx:arg" . "\0" . msgBind . "\0" . chatBind, secret)``.
 * ``msgBind``/``chatBind`` are the exact message-id/chat-id bound at encode
 * time ('' when null — the pre-send chat-bound wildcard); the recipient
 * recomputes over the values in hand, so any forged, tampered,
 * replayed-across-message, or cross-chat-relayed token decodes to null.
 *
 * ``keyId`` carries the menu's Q16 version byte; whether a key is still LIVE
 * is the registry's decision — a correctly signed token cannot expire
 * itself, so ``MenuRouter`` resolves liveness and answers "menu expired".
 */
final class CallbackData
{
    public const FORMAT = 'v1';

    /** Telegram's callback_data budget in bytes (Q15, hard constraint 2). */
    public const MAX_BYTES = 64;

    /** Truncated MAC length in raw bytes (base64url sig is ~14 chars). */
    private const SIGNATURE_BYTES = 10;

    /**
     * Sign one button: ``v1:<keyId>:<keyIdx>:<arg>:<sig>``. Rejects
     * delimiters inside the signed fields so the token stays colon-split-able
     * (zero-regex), and rejects tokens that would exceed the 64-byte budget.
     */
    public static function encode(
        string $keyId,
        int $keyIdx,
        string $arg,
        int|string|null $bindMsgId,
        int|string|null $bindChatId,
        string $secret,
    ): string {
        self::assertField($keyId, 'keyId');
        self::assertField($arg, 'arg', allowEmpty: true);
        if ($keyIdx < 0) {
            throw new InvalidArgumentException('keyIdx must be >= 0.');
        }

        $token = implode(':', [
            self::FORMAT,
            $keyId,
            (string) $keyIdx,
            $arg,
            self::sign($keyId, $keyIdx, $arg, $bindMsgId, $bindChatId, $secret),
        ]);

        if (strlen($token) > self::MAX_BYTES) {
            throw new InvalidArgumentException(
                'Signed callback token of ' . strlen($token)
                . ' bytes exceeds the 64-byte budget; shorten arg/keyId.',
            );
        }

        return $token;
    }

    /**
     * Verify and split a token. Returns ``{route, arg}`` where ``route`` is
     * the key reference ``<keyId>:<keyIdx>`` the registry resolves to an
     * uprate route; null on ANY mismatch — unknown format, malformed shape,
     * tampered field, rebound msg/chat, or wrong secret.
     *
     * @return array{route: string, arg: string}|null
     */
    public static function decode(
        string $token,
        int|string|null $bindMsgId,
        int|string|null $bindChatId,
        string $secret,
    ): ?array {
        $parts = explode(':', $token);
        if (count($parts) !== 5) {
            return null;
        }

        [$format, $keyId, $keyIdx, $arg, $sig] = $parts;

        if ($format !== self::FORMAT || $keyId === '' || $keyIdx === '' || ! ctype_digit($keyIdx)) {
            return null;
        }

        $expected = self::sign($keyId, (int) $keyIdx, $arg, $bindMsgId, $bindChatId, $secret);
        if (! hash_equals($sig, $expected)) {
            return null;
        }

        return ['route' => $keyId . ':' . $keyIdx, 'arg' => $arg];
    }

    private static function sign(
        string $keyId,
        int $keyIdx,
        string $arg,
        int|string|null $bindMsgId,
        int|string|null $bindChatId,
        string $secret,
    ): string {
        $message = implode(':', [$keyId, (string) $keyIdx, $arg])
            . "\0" . self::bind($bindMsgId)
            . "\0" . self::bind($bindChatId);

        $raw = hash_hmac('sha256', $message, $secret, true);

        return self::base64Url(substr($raw, 0, self::SIGNATURE_BYTES));
    }

    private static function bind(int|string|null $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    private static function assertField(string $value, string $name, bool $allowEmpty = false): void
    {
        if ((! $allowEmpty && $value === '') || str_contains($value, ':') || str_contains($value, "\0")) {
            throw new InvalidArgumentException("$name must be non-empty and free of ':' and NUL.");
        }
    }

    /** base64url (RFC 4648 §5), trailing '=' trimmed so the sig stays short. */
    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}