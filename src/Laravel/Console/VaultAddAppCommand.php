<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\VaultConfig;
use function Laravel\Prompts\text;

/**
 * artisan teleframe:vault-add-app — store one my.telegram.org app
 * (api_id/hash) in the DB vault, encrypted at rest.
 *
 * Manual-first: prints the my.telegram.org steps, prompts paste of the
 * id/hash (never the my.telegram.org password). A best-effort DOM helper
 * may pre-fill from a fetched page, but login stays manual.
 */
final class VaultAddAppCommand extends Command
{
    protected $signature = 'teleframe:vault-add-app
        {label : Vault label for this app (e.g. teleframe)}
        {--api-id= : api_id from https://my.telegram.org (prompts otherwise)}
        {--api-hash= : api_hash from https://my.telegram.org (prompts otherwise)}';

    protected $description = 'Store a my.telegram.org app (api_id/hash) in the encrypted DB vault';

    public function handle(): int
    {
        $label = (string) $this->argument('label');

        $this->components->info('Create the app at https://my.telegram.org first (login there yourself):');
        $this->components->twoColumnDetail('1.', 'Log in at https://my.telegram.org with your phone number.');
        $this->components->twoColumnDetail('2.', 'Open "API development tools" and create an app.');
        $this->components->twoColumnDetail('3.', 'Copy its api_id + api_hash and paste them below.');

        $apiId = (int) ($this->option('api-id') ?: text(
            'Telegram API ID',
            placeholder: 'from https://my.telegram.org',
            validate: static fn (string $v): ?string => ($v !== '' && strspn($v, '0123456789') === strlen($v) && (int) $v > 0)
                ? null : 'API ID must be a positive integer.',
        ));
        $apiHash = (string) ($this->option('api-hash') ?: text(
            'Telegram API Hash',
            placeholder: 'from https://my.telegram.org',
            validate: static fn (string $v): ?string => strlen($v) >= 30 ? null : 'API Hash looks too short.',
        ));

        if ($apiId <= 0 || $apiHash === '') {
            $this->components->error('API ID and API Hash are required.');

            return self::FAILURE;
        }

        TelegramApp::query()->updateOrCreate(
            ['label' => $label],
            ['api_id' => $apiId, 'api_hash' => $apiHash],
        );

        $this->components->info("App '{$label}' stored in the vault (encrypted).");

        return self::SUCCESS;
    }
}
