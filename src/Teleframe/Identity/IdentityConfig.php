<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity;

/**
 * Config/env accessor for the identity layer (Phase 5b).
 *
 * Every read tolerates BOTH a Laravel host (config() helper) and a plain-PHP
 * host (getenv()) — Laravel's config() wins because it is config-cache aware
 * (env() directly is hostile to `config:cache`). Values read lazily per call
 * so tests may putenv() between assertions.
 *
 * All lookups are substring/string-typed — zero-regex, per the engine rule.
 */
final class IdentityConfig
{
    /**
     * Q7 primary tenant: read config/env
     * `teleframe.primary_account_id` / `TELEFRAME_PRIMARY_ACCOUNT_ID`.
     * Null when the host never configured a primary account (callers then
     * fall back to "first account" or a null-account row as ruled).
     */
    public static function primaryAccountId(): ?int
    {
        $value = self::configOrEnv('teleframe.primary_account_id', 'TELEFRAME_PRIMARY_ACCOUNT_ID');

        return $value === null ? null : (int) $value;
    }

    /**
     * Q9 configured default sender:
     * `teleframe.identity.default_sender` / `TELEFRAME_IDENTITY_DEFAULT_SENDER`.
     * Shaped as a Bot API token (the delivery engine surface shipped today),
     * matching the config name's "which account/bot" intent.
     */
    public static function defaultSender(): ?string
    {
        return self::configOrEnv('teleframe.identity.default_sender', 'TELEFRAME_IDENTITY_DEFAULT_SENDER');
    }

    /**
     * Q10/initData freshness window in seconds:
     * `teleframe.identity.miniapp_auth_max_age` / `TELEFRAME_MINIAPP_AUTH_MAX_AGE`
     * — default 1800s (30 min, Telegram Mini App session lifetime). auth_date
     * absent OR older than the window => guard rejects the replayed session.
     */
    public static function miniappAuthMaxAge(): int
    {
        $value = self::configOrEnv('teleframe.identity.miniapp_auth_max_age', 'TELEFRAME_MINIAPP_AUTH_MAX_AGE');

        return $value === null ? 1800 : (int) $value;
    }

    /**
     * Configured bot token chain (identical fallback to the existing
     * VerifyMiniAppInitData middleware — this layer must agree with it word
     * for word so a Mini App accepted by `tg.miniapp` is accepted by the
     * `tg-webapp` guard).
     */
    public static function botToken(): ?string
    {
        return self::configOrEnv([
            'teleframe.bot_token',
            'teleframe.default_bot_token',
            'telegram.bot_token',
            'telegram.default_bot_token',
        ], 'TELEGRAM_BOT_TOKEN');
    }

    /**
     * First non-null value across the config keys, then the env var.
     *
     * @param string|list<string> $configKeys
     */
    private static function configOrEnv(string|array $configKeys, string $envKey): ?string
    {
        if (function_exists('config')) {
            foreach ((array) $configKeys as $key) {
                $value = config((string) $key);
                if ($value !== null && $value !== '' && $value !== false) {
                    return (string) $value;
                }
            }
        }

        $env = getenv($envKey);
        if ($env === false || $env === '') {
            return null;
        }

        return $env;
    }
}