<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Core\Support\EnvFile;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;

/**
 * artisan teleframe:vault-use-default — point the env default at one
 * named account (only the label lives in .env, secrets stay in DB).
 */
final class VaultUseDefaultCommand extends Command
{
    protected $signature = 'teleframe:vault-use-default
        {idOrLabel : Telegram user id (numeric) or vault account label}';

    protected $description = 'Point TELEFRAME_DEFAULT_ACCOUNT_ID (.env) at one vault account';

    public function handle(): int
    {
        $idOrLabel = (string) $this->argument('idOrLabel');

        // Numeric: look up by user_id (canonical identity)
        if (ctype_digit($idOrLabel) && (int) $idOrLabel > 0) {
            $account = TelegramAccount::query()->where('user_id', (int) $idOrLabel)->first();
            if ($account === null) {
                $this->components->error("no vault account found with Telegram id {$idOrLabel}.");

                return self::FAILURE;
            }

        // A REVOKED account is the one thing that must never become the
        // default: its auth key has been rejected by Telegram, so every
        // default-scoped call would fail at the first RPC — after the operator
        // had been told the switch succeeded. `vault:list` already surfaces
        // the flag; this refused to read it.
        if ($account->isRevoked()) {
            $this->components->error(
                "account {$idOrLabel} is REVOKED (its auth key was rejected by Telegram) — "
                .'re-run `php artisan teleframe:login` for it before making it the default.'
            );

            return self::FAILURE;
        }
            EnvFile::upsert(base_path('.env'), 'TELEFRAME_DEFAULT_ACCOUNT_ID', $idOrLabel);
            $this->components->info("Default vault account is now user_id {$idOrLabel} (saved to .env).");

            return self::SUCCESS;
        }

        // Legacy label fallback
        $account = TelegramAccount::query()->where('label', $idOrLabel)->first();
        if ($account === null) {
            $this->components->error("unknown vault account '{$idOrLabel}'.");

            return self::FAILURE;
        }

        // A REVOKED account is the one thing that must never become the
        // default: its auth key has been rejected by Telegram, so every
        // default-scoped call would fail at the first RPC — after the operator
        // had been told the switch succeeded. `vault:list` already surfaces
        // the flag; this refused to read it.
        if ($account->isRevoked()) {
            $this->components->error(
                "account {$idOrLabel} is REVOKED (its auth key was rejected by Telegram) — "
                .'re-run `php artisan teleframe:login` for it before making it the default.'
            );

            return self::FAILURE;
        }


        $userId = $account->getAttribute('user_id');
        $envValue = $userId !== null ? (string) $userId : $idOrLabel;

        EnvFile::upsert(base_path('.env'), 'TELEFRAME_DEFAULT_ACCOUNT_ID', $envValue);
        $this->components->info("Default vault account is now '{$idOrLabel}' (saved to .env).");

        return self::SUCCESS;
    }
}
