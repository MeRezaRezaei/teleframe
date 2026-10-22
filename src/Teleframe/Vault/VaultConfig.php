<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Vault;

use MeRezaRezaei\Teleframe\Identity\IdentityConfig;

/**
 * Config/env accessor for the vault default (IdentityConfig pattern:
 * Laravel config() wins — config-cache aware — getenv fallback; lazy
 * per call so tests may putenv() between assertions; zero-regex).
 */
final class VaultConfig
{
    /**
     * Default vault account id (Telegram user id, numeric) or legacy label:
     * `teleframe.vault.default_account` / TELEFRAME_DEFAULT_ACCOUNT_ID,
     * else the identity primary account id chain (same tenant default),
     * else null (env TELEGRAM_* fallback).
     *
     * Vault::defaultAccount() handles the id-vs-label resolution.
     */
    public static function defaultAccountId(): ?string
    {
        $value = self::configOrEnv('teleframe.vault.default_account', 'TELEFRAME_DEFAULT_ACCOUNT_ID');
        if ($value !== null) {
            return $value;
        }

        $primary = IdentityConfig::primaryAccountId();

        return $primary === null ? null : (string) $primary;
    }

    /**
     * @deprecated Use defaultAccountId() — kept for backward compat.
     */
    public static function defaultAccountLabel(): ?string
    {
        return self::defaultAccountId();
    }

    private static function configOrEnv(string $configKey, string $envKey): ?string
    {
        if (function_exists('config')) {
            $value = config($configKey);
            if ($value !== null && $value !== '' && $value !== false) {
                return (string) $value;
            }
        }

        $env = getenv($envKey);
        if ($env === false || $env === '') {
            return null;
        }

        return $env;
    }
}
