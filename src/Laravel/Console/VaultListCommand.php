<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;

/**
 * artisan teleframe:vault-list — show vault labels without leaking
 * secrets (labels + kinds + link state only).
 */
final class VaultListCommand extends Command
{
    protected $signature = 'teleframe:vault-list';

    protected $description = 'List vault apps + accounts (labels only, no secrets)';

    public function handle(): int
    {
        /** @var array<array{string, string}> $appRows */
        $appRows = [];
        foreach (TelegramApp::query()->orderBy('label')->get(['id', 'label', 'api_id']) as $a) {
            /** @var TelegramApp $a */
            $appRows[] = [(string) $a->getAttribute('label'), (string) $a->getAttribute('api_id')];
        }
        $this->components->info('Apps:');
        $this->table(['Label', 'api_id'], $appRows);

        // Deploy-order guard: on a host whose vault table predates the
        // revocation migration, selecting the column would be a hard SQL
        // error — and this command is exactly what an operator runs to find
        // out WHY their accounts are failing, so it must not be the thing
        // that breaks.
        $hasRevokedAt = Schema::hasColumn('telegram_accounts', 'revoked_at');
        $columns = ['label', 'type', 'user_id', 'app_id', 'dc_id'];
        if ($hasRevokedAt) {
            $columns[] = 'revoked_at';
        }

        /**
         * Accounts, with the revocation state folded into the Type cell rather
         * than added as a column: the header set is a published surface (tests
         * and host scripts match on it), and a revoked account is a property of
         * the row, not a new axis. `user (revoked)` is the answer an operator
         * needs — it is the only thing that tells them this account needs
         * re-login — and a healthy row's output is byte-identical to before.
         *
         * @var array<array{string, string, string, string, string}> $accountRows
         */
        $accountRows = [];
        foreach (TelegramAccount::query()->orderBy('label')->get($columns) as $a) {
            /** @var TelegramAccount $a */
            $appId = $a->getAttribute('app_id');
            $userId = $a->getAttribute('user_id');
            $type = (string) $a->getAttribute('type');
            $accountRows[] = [
                (string) $a->getAttribute('label'),
                $hasRevokedAt && $a->isRevoked() ? $type.' (revoked)' : $type,
                $userId === null ? '—' : (string) $userId,
                $appId === null ? '—' : (string) $appId,
                (string) $a->getAttribute('dc_id'),
            ];
        }
        $this->components->info('Accounts:');
        $this->table(['Label', 'Type', 'Telegram ID', 'app_id', 'dc_id'], $accountRows);

        if ($hasRevokedAt && TelegramAccount::query()->whereNotNull('revoked_at')->exists()) {
            // The listing says WHICH accounts are dead; this says what to do
            // about it, and names the only command that fixes it.
            $this->components->warn(
                'Revoked account(s) above need a fresh login: run "php artisan teleframe:login".'
            );
        }

        return self::SUCCESS;
    }
}
