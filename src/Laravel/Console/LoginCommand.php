<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Support\EnvFile;
use MeRezaRezaei\Teleframe\Core\Support\TerminalQr;
use MeRezaRezaei\Teleframe\Laravel\Exceptions\ResendLimitExceededException;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
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
                            {--show-session : Print the FULL session string to stdout (it is the complete auth key — a credential, never logged by default)}
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
                'qr' => '📷 User Account: Scan QR Code with Telegram App',
                'bot' => '🤖 Bot Account: High-Speed MTProto Bot Token',
            ],
            default: 'phone'
        );

        return match ($choice) {
            'phone' => $this->handlePhoneLogin($authService, $apiId, $apiHash, $dcId),
            'qr' => $this->handleQrLogin($authService, $apiId, $apiHash, $dcId),
            'bot' => $this->handleBotLogin($authService, $apiId, $apiHash, $dcId),
            default => self::FAILURE,
        };
    }

    protected function handlePhoneLogin(TeleframeAuthService $authService, int $apiId, string $apiHash, int $dcId): int
    {
        $phone = (string) ($this->option('phone') ?: text(
            'Phone number (international)',
            placeholder: '+989123456789',
            validate: fn (string $v) => (Str::startsWith($v, '+') && strlen($v) > 8 && strspn(substr($v, 1), '0123456789') === strlen($v) - 1) ? null : 'Use full international format, e.g. +989123456789.'
        ));
        if (empty($phone)) {
            $this->components->error('Phone number cannot be empty.');

            return self::FAILURE;
        }

        $this->components->task('Connecting to Telegram DC '.$dcId.' and requesting login code...', function () {
            return true;
        });

        try {
            $result = $authService->sendPhoneCode($phone, $apiId, $apiHash, $dcId);
            $user = $result['user'];
            $session = $result['session'];
            $phoneCodeHash = $result['phone_code_hash'];
        } catch (FloodWaitException $e) {
            return $this->floodWaitFailure($e);
        } catch (TelegramException $e) {
            $this->components->error('Login failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Verification code sent to your Telegram app or SMS.');

        // Bounded by the service resend cap: each extra attempt costs one
        // consented auth.resendCode, never an automatic retry. A stale code
        // ends the loop instead of looping for a fresh one.
        for ($attempt = 0; $attempt <= TeleframeAuthService::MAX_RESENDS_PER_LOGIN; $attempt++) {
            $code = text('Login code', required: true);

            try {
                $outcome = $authService->attemptCodeSignIn($user, $phone, $phoneCodeHash, $code);
            } catch (TelegramException $e) {
                if (str_contains($e->getMessage(), 'SESSION_PASSWORD_NEEDED')) {
                    return $this->handle2faStep($authService, $user, $session, 'TELEGRAM_USER_SESSION', 'User Account');
                }
                if ($e instanceof FloodWaitException) {
                    return $this->floodWaitFailure($e);
                }
                throw $e;
            }

            if ($outcome['status'] === 'signed_in') {
                return $this->finalizeLogin($session, 'TELEGRAM_USER_SESSION', 'User Account');
            }

            if ($outcome['status'] === 'flood_wait') {
                $this->components->error('Telegram flood protection: wait '.$outcome['seconds'].' second(s) before signing in again.');
                $this->components->warn('No code was resent. Re-run teleframe:login when the wait is over.');

                return self::FAILURE;
            }

            if ($outcome['status'] === 'code_rejected') {
                $this->components->error((string) $outcome['error']);

                return self::FAILURE;
            }

            // status = code_expired: the code Telegram sent is spent.
            $this->components->warn((string) $outcome['error']);

            if ($authService->resendsRemaining() <= 0) {
                $this->components->error(
                    'Refusing to resend: '.$authService->resendsUsed().' of '.TeleframeAuthService::MAX_RESENDS_PER_LOGIN
                    .' resends already used in this login attempt. A new code needs a fresh teleframe:login run (auth.sendCode).'
                );

                return self::FAILURE;
            }

            if (! $this->confirm(
                'Send a new code now? (resend '.($authService->resendsUsed() + 1).' of '.TeleframeAuthService::MAX_RESENDS_PER_LOGIN.')',
                false
            )) {
                $this->components->error('Login aborted at your request; no further codes were sent.');

                return self::FAILURE;
            }

            try {
                $resend = $authService->resendCode($user, $phone, $phoneCodeHash);
            } catch (ResendLimitExceededException $e) {
                $this->components->error($e->getMessage());

                return self::FAILURE;
            } catch (FloodWaitException $e) {
                return $this->floodWaitFailure($e);
            }

            if ($resend['status'] !== 'sent') {
                $this->components->error('Telegram refused the resend: '.(string) $resend['error']);
                $this->components->warn('Re-run teleframe:login to request a brand new code (auth.sendCode).');

                return self::FAILURE;
            }

            $phoneCodeHash = (string) $resend['phone_code_hash'];
            $this->components->info('New code sent. Enter it below.');
        }

        $this->components->error('Login gave up after '.TeleframeAuthService::MAX_RESENDS_PER_LOGIN.' resends. Re-run teleframe:login for a fresh code.');

        return self::FAILURE;
    }

    /**
     * Flood waits are reported with the seconds Telegram demanded and nothing
     * is retried or resent — the wait is the operator's to observe.
     */
    protected function floodWaitFailure(FloodWaitException $e): int
    {
        $this->components->error('Telegram flood protection: wait '.$e->seconds.' second(s) before trying again.');
        $this->components->warn('No code was resent. Re-run teleframe:login once the wait is over.');

        return self::FAILURE;
    }

    protected function handleQrLogin(TeleframeAuthService $authService, int $apiId, string $apiHash, int $dcId): int
    {
        $this->components->info('Initializing QR Code Login session...');

        try {
            $qrRes = $authService->exportQrLoginToken($apiId, $apiHash, $dcId);
            $session = $qrRes['session'];
            $loginUrl = $qrRes['url'];

            $this->line(TerminalQr::renderOrUrl($loginUrl));
            $this->components->info('1. Open Telegram on your phone -> Settings -> Devices -> Link Desktop Device.');
            $this->components->info('2. Scan the QR code above or open link: '.$loginUrl);

            $this->components->task('Waiting for QR code confirmation in Telegram...', function () {
                return true;
            });

            return $this->finalizeLogin($session, 'TELEGRAM_USER_SESSION', 'User Account (QR)');
        } catch (TelegramException $e) {
            $this->components->error('QR Login failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function handleBotLogin(TeleframeAuthService $authService, int $apiId, string $apiHash, int $dcId): int
    {
        $botToken = (string) (config('teleframe.bot_token') ?: text('Bot token (from @BotFather)', placeholder: '123456:ABC-DEF...', required: true));
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

        $authService->check2faPassword($userScope, (string) $password);

        return $this->finalizeLogin($session, $envKey, $label);
    }

    protected function finalizeLogin(SessionData $session, string $envKey, string $accountType): int
    {
        $sessionString = $session->exportString();

        $storedInVault = $this->storeSessionInVault($sessionString, $accountType);

        $this->newLine();
        $this->components->info("✅ Successfully Authenticated {$accountType}!");

        $this->table(
            ['Property', 'Value'],
            [
                ['Account Type', $accountType],
                ['Data Center', 'DC '.$session->dcId],
                ['AuthKey Length', strlen($session->authKey).' bytes'],
                ['Session String', substr($sessionString, 0, 24).'...'.substr($sessionString, -12)],
            ]
        );

        // The full session string is base64(dcId:base64(authKey):userId:delta:salt)
        // — the COMPLETE AUTH KEY, i.e. full account takeover. It used to be
        // echoed unconditionally BEFORE the .env/vault prompt, so an operator
        // who chose to store it safely still got it on stdout, where
        // `| tee login.log`, a CI job or a screen share keeps it in
        // cleartext — contradicting this package's own rule at
        // Vault\TelegramAccount (a session string is a credential, never
        // logged). It is now shown only behind an explicit --show-session
        // opt-in, and the default output says WHERE it went instead.
        if ($this->option('show-session')) {
            $this->newLine();
            $this->line('<fg=cyan>Exported Session String (--show-session: treat as a credential — the full auth key):</>');
            $this->line("<fg=yellow>{$sessionString}</>");
        }

        $savedToEnv = false;

        if ($this->confirm("Would you like to save this session to your .env file as {$envKey}?", true)) {
            EnvFile::upsert(base_path('.env'), $envKey, $sessionString);
            $savedToEnv = true;
            $this->components->info("Saved to .env as {$envKey}.");
            $this->line('The session string is not printed (pass --show-session to print it).');
        } else {
            $this->components->warn('Session string NOT saved to .env.');
            $this->line(
                $storedInVault
                    ? 'Re-run with --show-session if you need to copy it out of the vault.'
                    : 'Re-run with --show-session (or --account=LABEL) if you need the string at all — an unsaved session is lost when this process exits.',
            );
        }

        // A SUCCESS here used to mean "you are logged in", which is true and
        // useless: what the caller needs to know is whether the credential
        // still EXISTS after this process exits. Answering no at the prompt
        // with no --account=LABEL writes the session NOWHERE — not .env, not
        // the vault — and the default output path deliberately does not print
        // it, so the auth key is destroyed the moment the process returns. A
        // provisioning script, a CI step or a `&&` chain reads that 0 as "the
        // account is provisioned", and re-provisioning costs the operator a
        // fresh login plus a fresh 2FA.
        //
        // So the exit code answers the question the contract is actually about:
        // did the credential land somewhere durable? Printed to stdout is NOT
        // durable — that is stdout on a CI log, which this package treats as a
        // credential leak (see --show-session at :307), not as a store.
        if (! $savedToEnv && ! $storedInVault) {
            $this->components->error(
                'The session was saved NOWHERE — not to .env (declined) and not to the vault '
                .'(no --account=LABEL given, or the vault write failed).'
            );
            $this->line(
                'This is NOT a logged-in state you can rely on: the auth key is gone when this process exits. '
                .'Re-run and answer yes, or pass --account=LABEL to store it in the vault.'
            );

            return self::FAILURE;
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
            $this->components->error('Vault unreachable (run migrations first): '.$e->getMessage());

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
     *
     * @return bool true when the session reached the vault, so the caller can
     *              say WHERE the credential is instead of printing it
     */
    protected function storeSessionInVault(string $sessionString, string $accountType): bool
    {
        $label = (string) ($this->option('account') ?? '');
        if ($label === '') {
            return false;
        }

        try {
            $account = TelegramAccount::query()->where('label', $label)->first();
            if ($account === null) {
                $this->components->warn("unknown vault account '{$label}' — session kept in .env flow only.");

                return false;
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

            return true;
        } catch (\Throwable $e) {
            $this->components->warn('Vault store skipped (migrations not run?): '.$e->getMessage());

            return false;
        }
    }
}
