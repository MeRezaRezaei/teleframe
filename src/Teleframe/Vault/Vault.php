<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Vault;

/**
 * Named credential resolver over the DB vault (Task 3).
 *
 * Exact-match only (TlUserBinding::bindingFor tenancy rule): explicit
 * label => that row or null, never a cross-tenant scan. Default chain:
 * `teleframe.vault.default_account` / TELEFRAME_DEFAULT_ACCOUNT_ID, then
 * the identity primary account id, then null (callers fall back to
 * TELEGRAM_* env through TeleframeClient defaults).
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

    public function defaultAccount(): ?TelegramAccount
    {
        $label = VaultConfig::defaultAccountLabel();
        if ($label === null) {
            return null;
        }

        return $this->account($label);
    }
}
