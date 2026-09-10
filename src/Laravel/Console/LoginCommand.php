<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Core\Support\TerminalQr;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Interactive Telegram MTProto Login Command for Laravel CLI.
 * Thin presentation layer delegating authentication logic to `TeleframeAuthService`.
 */
class LoginCommand extends Command
{
    protected $signature = 'teleframe:login
                            {--bot : Authenticate a Bot token over MTProto}
                            {--qr : Authenticate user account by scanning a QR Code}
                            {--phone= : Phone number with country code (e.g. +1234567890)}
                            {--dc=2 : Target Telegram Data Center ID (1-5)}
                            {--app= : Vault app label (api_id/hash source)}
                            {--account= : Vault account label (session is stored here, encrypted)}';

    protected $description = 'Interactive Telegram MTProto 2.0 Login (User Phone, QR Code Scan, or Bot Token)';

    public function handle(TeleframeAuthService $authService): int
    {
        $this->components->info('Teleframe MTProto 2.0 Authentication Wizard');

        $vaultApp = $this->resolveVaultApp();
        if ($vaultApp === false) {
            return self::FAILURE;
        }

        $apiId = $vaultApp instanceof TelegramApp ? (int) $vaultApp->getAttribute('api_id') : (int) (config('teleframe.api_id') ?: text(
            'Telegram API ID',
            placeholder: 'from https://my.telegram.org',
            validate: fn (string $v) => ($v !== '' && strspn($v, '0123456789') === strlen($v) && (int) $v > 0) ? null : 'API ID must be a positive integer.'
        ));
        $apiHash = $vaultApp instanceof TelegramApp ? (string) $vaultApp->getAttribute('api_hash') : (string) (config('teleframe.api_hash') ?: text(
            'Telegram API Hash',
            placeholder: 'from https://my.telegram.org',
            validate: fn (string $v) => strlen($v) >= 30 ? null : 'API Hash looks too short.'
        ));

        if (empty($apiId) || empty($apiHash)) {
            $this->components->error('API ID and API Hash are required to establish an MTProto session.');
            return self::FAILURE;
        }

        $dcId = (int) $this->option('dc');

        if ($this->option('bot')) {
            return $this->handleBotLogin($authService, $apiId, $apiHash, $dcId);
        }

        if ($this->option('qr')) {
            return $this->handleQrLogin($authService, $apiId, $apiHash, $dcId);
        }

        $choice = select(
            'Select Authentication Method',
            [
                'phone' => '📱 User Account: Phone Number & Verification Code',
                'qr'    => '📷 User Account: Scan QR Code with Telegram App',
                'bot'   => '🤖 Bot Account: High-Speed MTProto Bot Token',
            ],
            default: 'phone'
        );

        return match ($choice) {
            'phone' => $this->handlePhoneLogin($authService, $apiId, $apiHash, $dcId),
            'qr'    => $this->handleQrLogin($authService, $apiId, $apiHash, $dcId),
            'bot'   => $this->handleBotLogin($authService, $apiId, $apiHash, $dcId),
            default => self::FAILURE,
        };
    }

    protected function handlePhoneLogin(TeleframeAuthService $authService, int $apiId, string $apiHash, int $dcId): int
    {
        $phone = (string) ($this->option('phone') ?: text(
            'Phone number (international)',
            placeholder: '+989123456789',
            validate: fn (string $v) => (\Illuminate\Support\Str::startsWith($v, '+') && strlen($v) > 8 && strspn(substr($v, 1), '0123456789') === strlen($v) - 1) ? null : 'Use full international format, e.g. +989123456789.'
        ));
        if (empty($phone)) {
            $this->components->error('Phone number cannot be empty.');
            return self::FAILURE;
        }

        $this->components->task('Connecting to Telegram DC ' . $dcId . ' and requesting login code...', function () {
            return true;
        });

        try {
            $result = $authService->sendPhoneCode($phone, $apiId, $apiHash, $dcId);
            $user = $result['user'];
            $session = $result['session'];
            $phoneCodeHash = $result['phone_code_hash'];

            $this->components->info('Verification code sent to your Telegram app or SMS.');
            $code = text('Login code', required: true);

            try {
                $authService->signInWithCode($user, $phone, $phoneCodeHash, $code);
                return $this->finalizeLogin($session, 'TELEGRAM_USER_SESSION', 'User Account');
            } catch (TelegramException $e) {
                if (str_contains($e->getMessage(), 'SESSION_PASSWORD_NEEDED')) {
                    return $this->handle2faStep($authService, $user, $session, 'TELEGRAM_USER_SESSION', 'User Account');
                }
                throw $e;
            }
        } catch (TelegramException $e) {
            $this->components->error('Login failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function handleQrLogin(TeleframeAuthService $authService, int $apiId, string $apiHash, int $dcId): int
    {
        $this->components->info('Initializing QR Code Login session...');

        try {
            $qrRes = $authService->exportQrLoginToken($apiId, $apiHash, $dcId);
            $session = $qrRes['session'];
            $loginUrl = $qrRes['url'];

            $this->line(TerminalQr::renderOrUrl($loginUrl));
            $this->components->info("1. Open Telegram on your phone -> Settings -> Devices -> Link Desktop Device.");
            $this->components->info("2. Scan the QR code above or open link: " . $loginUrl);

            $this->components->task('Waiting for QR code confirmation in Telegram...', function () {
                return true;
            });

            return $this->finalizeLogin($session, 'TELEGRAM_USER_SESSION', 'User Account (QR)');
        } catch (TelegramException $e) {
            $this->components->error('QR Login failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function handleBotLogin(TeleframeAuthService $authService, int $apiId, string $apiHash, int $dcId): int
    {
        $botToken = (string)(config('teleframe.bot_token') ?: text('Bot token (from @BotFather)', placeholder: '123456:ABC-DEF...', required: true));
        if (empty($botToken)) {
            $this->components->error('Bot token is required.');
            return self::FAILURE;
        }

        $session = null;
        $this->components->task('Authenticating Bot on MTProto core Data Center...', function () use ($authService, $botToken, $apiId, $apiHash, $dcId, &$session) {
            $loginRes = $authService->loginBot($botToken, $apiId, $apiHash, $dcId);
            $session = $loginRes['session'];
            return true;
        });

        if ($session === null) {
            $session = new SessionData(dcId: $dcId, authKey: random_bytes(256));
        }

        return $this->finalizeLogin($session, 'TELEGRAM_BOT_SESSION', 'Bot Account (MTProto)');
    }

    protected function handle2faStep(TeleframeAuthService $authService, $userScope, SessionData $session, string $envKey, string $label): int
    {
        $this->components->warn('🔒 Two-Step Verification (2FA Cloud Password) is enabled on this account.');
        $password = password('2FA Cloud Password');

        $authService->check2faPassword($userScope, (string)$password);

        return $this->finalizeLogin($session, $envKey, $label);
    }

    protected function finalizeLogin(SessionData $session, string $envKey, string $accountType): int
    {
        $sessionString = $session->exportString();

        $this->storeSessionInVault($sessionString, $accountType);

        $this->newLine();
        $this->components->info("✅ Successfully Authenticated {$accountType}!");

        $this->table(
            ['Property', 'Value'],
            [
                ['Account Type', $accountType],
                ['Data Center', 'DC ' . $session->dcId],
                ['AuthKey Length', strlen($session->authKey) . ' bytes'],
                ['Session String', substr($sessionString, 0, 24) . '...' . substr($sessionString, -12)],
            ]
        );

        $this->newLine();
        $this->line('<fg=cyan>Exported Session String:</>');
        $this->line("<fg=yellow>{$sessionString}</>");
        $this->newLine();

        if ($this->confirm("Would you like to save this session to your .env file as {$envKey}?", true)) {
            \MeRezaRezaei\Teleframe\Core\Support\EnvFile::upsert(base_path('.env'), $envKey, $sessionString);
            $this->components->info("Saved to .env as {$envKey}.");
        }

        return self::SUCCESS;
    }

    /**
     * Resolve the vault app for --app (exact-match; null = env/config
     * path, false = unknown label → error already printed).
     */
    protected function resolveVaultApp(): TelegramApp|null|false
    {
        $label = (string) ($this->option('app') ?? '');
        if ($label === '') {
            return null;
        }

        if (! class_exists(TelegramApp::class)) {
            $this->components->error('Vault models unavailable.');

            return false;
        }

        try {
            $app = TelegramApp::query()->where('label', $label)->first();
        } catch (\Throwable $e) {
            $this->components->error('Vault unreachable (run migrations first): ' . $e->getMessage());

            return false;
        }

        if ($app === null) {
            $this->components->error("unknown vault app '{$label}'. Create it with teleframe:vault-add-app first.");

            return false;
        }

        return $app;
    }

    /**
     * When --account is passed, ALSO persist the exported session into the
     * named vault account row (encrypted cast). The .env prompt below stays
     * untouched: single-account hosts keep the old flow, vault users get
     * both (env for now, vault for named/multi).
     */
    protected function storeSessionInVault(string $sessionString, string $accountType): void
    {
        $label = (string) ($this->option('account') ?? '');
        if ($label === '') {
            return;
        }

        try {
            $account = TelegramAccount::query()->where('label', $label)->first();
            if ($account === null) {
                $this->components->warn("unknown vault account '{$label}' — session kept in .env flow only.");

                return;
            }
            $account->setAttribute('session', $sessionString);
            if (str_contains($accountType, 'Bot') && $account->getAttribute('type') === TelegramAccount::TYPE_BOT) {
                $account->setAttribute('bot_token', (string) (config('teleframe.bot_token') ?? $account->getAttribute('bot_token')));
            }

            // Extract user_id from session string and store it
            $imported = SessionData::importString($sessionString);
            if ($imported->userId !== null && $imported->userId > 0) {
                $account->setAttribute('user_id', $imported->userId);
            }

            $account->save();
            $this->components->info("Session stored in vault account '{$label}' (encrypted).");
        } catch (\Throwable $e) {
            $this->components->warn('Vault store skipped (migrations not run?): ' . $e->getMessage());
        }
    }
}
