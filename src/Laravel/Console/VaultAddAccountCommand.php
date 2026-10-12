<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;
use function Laravel\Prompts\text;

/**
 * artisan teleframe:vault-add-account — register one named account row in
 * the DB vault. `user` rows link an app (api_id/hash source); `bot` rows
 * take a token (HTTP needs app_id null).
 */
final class VaultAddAccountCommand extends Command
{
    protected $signature = 'teleframe:vault-add-account
        {label : Vault label for this account (e.g. main)}
        {--app= : App label in the vault (required for type=user)}
        {--type=user : Account kind: user|bot}
        {--telegram-id= : Telegram user/account id (numeric, set after login or manually)}
        {--bot-token= : Bot token from @BotFather (type=bot)}
        {--dc=2 : Target Telegram Data Center ID (1-5)}';

    protected $description = 'Register a named user/bot account in the encrypted DB vault';

    public function handle(): int
    {
        $label = (string) $this->argument('label');
        $type = strtolower((string) $this->option('type'));
        if ($type !== TelegramAccount::TYPE_USER && $type !== TelegramAccount::TYPE_BOT) {
            $this->components->error("type must be 'user' or 'bot'.");

            return self::FAILURE;
        }

        $appId = null;
        $appLabel = (string) ($this->option('app') ?? '');
        if ($type === TelegramAccount::TYPE_USER) {
            if ($appLabel === '') {
                $this->components->error('type=user requires --app (the vault app label).');

                return self::FAILURE;
            }
            $app = TelegramApp::query()->where('label', $appLabel)->first();
            if ($app === null) {
                $this->components->error("unknown vault app '{$appLabel}'. Create it with teleframe:vault-add-app first.");

                return self::FAILURE;
            }
            $appId = (int) $app->getAttribute('id');
        } elseif ($appLabel !== '') {
            $found = TelegramApp::query()->where('label', $appLabel)->first();
            $appId = $found === null ? null : (int) $found->getAttribute('id');
        }

        $botToken = null;
        if ($type === TelegramAccount::TYPE_BOT) {
            $botToken = (string) ($this->option('bot-token') ?: '');
            if ($botToken === '') {
                $botToken = (string) text('Bot token (from @BotFather)', placeholder: '123456:ABC-DEF...', required: true);
            }
            if ($botToken === '') {
                $this->components->error('Bot token is required for type=bot.');

                return self::FAILURE;
            }
        }

        $telegramId = (string) ($this->option('telegram-id') ?: '');
        $telegramIdValue = ($telegramId !== '' && ctype_digit($telegramId) && (int) $telegramId > 0)
            ? (int) $telegramId
            : null;

        TelegramAccount::query()->updateOrCreate(
            ['label' => $label],
            [
                'user_id' => $telegramIdValue,
                'app_id' => $appId,
                'type' => $type,
                'bot_token' => $botToken,
                'dc_id' => max(1, min(5, (int) $this->option('dc'))),
            ],
        );

        $this->components->info("Account '{$label}' ({$type}) stored in the vault (encrypted).");

        if ($type === TelegramAccount::TYPE_USER) {
            $this->components->info("Next: php artisan teleframe:login --app={$appLabel} --account={$label} to sign it in.");
        }

        return self::SUCCESS;
    }
}
