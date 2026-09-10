<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
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

        /** @var array<array{string, string, string, string, string}> $accountRows */
        $accountRows = [];
        foreach (TelegramAccount::query()->orderBy('label')->get(['label', 'type', 'user_id', 'app_id', 'dc_id']) as $a) {
            /** @var TelegramAccount $a */
            $appId = $a->getAttribute('app_id');
            $userId = $a->getAttribute('user_id');
            $accountRows[] = [
                (string) $a->getAttribute('label'),
                (string) $a->getAttribute('type'),
                $userId === null ? '—' : (string) $userId,
                $appId === null ? '—' : (string) $appId,
                (string) $a->getAttribute('dc_id'),
            ];
        }
        $this->components->info('Accounts:');
        $this->table(['Label', 'Type', 'Telegram ID', 'app_id', 'dc_id'], $accountRows);

        return self::SUCCESS;
    }
}
