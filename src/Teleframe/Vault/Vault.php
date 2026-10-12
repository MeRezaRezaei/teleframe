<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Vault;

/**
 * Named credential resolver over the DB vault (Task 3).
 *
 * Resolution strategy (defaultAccount):
 * 1. TELEFRAME_DEFAULT_ACCOUNT_ID is a Telegram user id (numeric) —
 *    looked up via `user_id` column (globally unique across accounts).
 * 2. Legacy fallback: treat the value as a label (backward compat).
 * 3. Null → callers fall back to TELEGRAM_* env via TeleframeClient.
 */
final class Vault
{
    public function app(string $label): ?TelegramApp
    {
        return TelegramApp::query()->where('label', $label)->first();
    }

    public function account(string $label): ?TelegramAccount
    {
        return TelegramAccount::query()->where('label', $label)->first();
    }

    public function accountByUserId(int $userId): ?TelegramAccount
    {
        return TelegramAccount::query()->where('user_id', $userId)->first();
    }

    public function defaultAccount(): ?TelegramAccount
    {
        $idOrLabel = VaultConfig::defaultAccountId();
        if ($idOrLabel === null) {
            return null;
        }

        // Primary: numeric Telegram user id (the canonical identity)
        if (ctype_digit($idOrLabel) && (int) $idOrLabel > 0) {
            $account = $this->accountByUserId((int) $idOrLabel);
            if ($account !== null) {
                return $account;
            }
        }

        // Legacy fallback: treat as label
        return $this->account($idOrLabel);
    }
}
