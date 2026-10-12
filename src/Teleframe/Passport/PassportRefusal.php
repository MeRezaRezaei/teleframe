<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;

/**
 * PassportRefusal (Cycle 18 W3) — classifies a wire refusal so the passport
 * login can ROUTE it instead of dropping it. The user's success criteria put it
 * plainly: *"PASSWORD_REQUIRED routes to the existing PasswordConfirmer;
 * SecureValueError surfaces per-field errors."* Routing needs the refusal's
 * identity, so this is the one place that reads it.
 *
 * `RpcErrorException` exposes the bare token (`rpcErrorMessage` =
 * "PASSWORD_REQUIRED") plus the numeric code; when a caller re-throws a
 * flattened copy instead, the token is still recovered from the message by
 * substring lookup over a closed list. Closed list, not a search: a refusal
 * this class cannot name is a refusal it does not act on, and it says so.
 *
 * Zero-regex: `str_contains` over a fixed token list. The list is ordered so
 * the more specific token is matched before any token it contains.
 */
final class PassportRefusal
{
    /** 400 — "A 2FA password must be configured to use Telegram Passport." */
    public const PASSWORD_REQUIRED = 'PASSWORD_REQUIRED';

    /** 401 — the SRP flow must complete first. */
    public const SESSION_PASSWORD_NEEDED = 'SESSION_PASSWORD_NEEDED';

    /** 400 — the exported authorization blob is malformed/expired. */
    public const AUTH_BYTES_INVALID = 'AUTH_BYTES_INVALID';

    /** 401 — the session's auth key is gone; the import cannot be attributed. */
    public const AUTH_KEY_UNREGISTERED = 'AUTH_KEY_UNREGISTERED';

    /** 401 — the user revoked this session mid-flow. */
    public const SESSION_REVOKED = 'SESSION_REVOKED';

    /** 401 — the session expired mid-flow. */
    public const SESSION_EXPIRED = 'SESSION_EXPIRED';

    /** 400 — the imported authorization's user id is not usable. */
    public const USER_ID_INVALID = 'USER_ID_INVALID';

    /** 400 — passport is user-only; a bot cannot be imported into a login. */
    public const USER_IS_BOT = 'USER_IS_BOT';

    /** 400 — the DC id in the authorization is not a valid target. */
    public const DC_ID_INVALID = 'DC_ID_INVALID';

    /**
     * Every token this class recognises, most specific first.
     *
     * @var list<string>
     */
    public const TOKENS = [
        self::SESSION_PASSWORD_NEEDED,
        self::AUTH_KEY_UNREGISTERED,
        self::SESSION_REVOKED,
        self::SESSION_EXPIRED,
        self::AUTH_BYTES_INVALID,
        self::PASSWORD_REQUIRED,
        self::USER_ID_INVALID,
        self::USER_IS_BOT,
        self::DC_ID_INVALID,
    ];

    /** Nothing recognisable — the caller must not guess a route. */
    public const UNKNOWN = '';

    /**
     * The bare refusal token for an exception, or self::UNKNOWN.
     *
     * A typed `RpcErrorException` is read from its own field (exact); anything
     * else falls back to a substring search over TOKENS, so a wrapped or
     * re-worded copy still routes.
     */
    public static function tokenOf(\Throwable $e): string
    {
        if ($e instanceof RpcErrorException) {
            return $e->rpcErrorMessage;
        }

        $message = strtoupper($e->getMessage());
        foreach (self::TOKENS as $token) {
            if (str_contains($message, $token)) {
                return $token;
            }
        }

        return self::UNKNOWN;
    }

    /** True when the refusal is exactly this token. */
    public static function is(\Throwable $e, string $token): bool
    {
        return self::tokenOf($e) === $token;
    }

    /**
     * True when Telegram demands a 2FA password for this call — the signal
     * that routes to the existing PasswordConfirmer (its 2FA role untouched)
     * instead of failing the login.
     *
     * Both `PASSWORD_REQUIRED` (documented for passport: "must configure 2FA
     * to use Telegram Passport", RpcErrorCatalog) and `SESSION_PASSWORD_NEEDED`
     * (the equivalent 2FA gate) count: refusing a login over either would be
     * a silent drop of a recoverable condition.
     */
    public static function passwordRequired(\Throwable $e): bool
    {
        $token = self::tokenOf($e);

        return $token === self::PASSWORD_REQUIRED || $token === self::SESSION_PASSWORD_NEEDED;
    }

    /** The numeric rpc code when the exception carries one, else 0. */
    public static function codeOf(\Throwable $e): int
    {
        return $e instanceof RpcErrorException ? $e->rpcErrorCode : 0;
    }
}
