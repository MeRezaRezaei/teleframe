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
        {label : Vault account label to make the default}';

    protected $description = 'Point TELEFRAME_DEFAULT_ACCOUNT_ID (.env) at one vault account';

    public function handle(): int
    {
        $label = (string) $this->argument('label');
        $account = TelegramAccount::query()->where('label', $label)->first();
        if ($account === null) {
            $this->components->error("unknown vault account '{$label}'.");

            return self::FAILURE;
        }

        EnvFile::upsert(base_path('.env'), 'TELEFRAME_DEFAULT_ACCOUNT_ID', $label);
        $this->components->info("Default vault account is now '{$label}' (saved to .env).");

        return self::SUCCESS;
    }
}
