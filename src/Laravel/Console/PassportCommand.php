<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\NonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Passport\PassportWireLogin;
use MeRezaRezaei\Teleframe\Passport\PasswordRequiredException;
use RuntimeException;

/**
 * artisan teleframe:passport (design 2026-09-24 §7): the CLI shell over the
 * Telegram Passport login surface. Follows the NotifyCommand ACTIONS/match
 * pattern: unknown action or RuntimeException → error + FAILURE.
 *
 * Actions:
 *   status  — enabled? key path (NEVER the PEM), tenant account, nonce TTL
 *             + store, allowed types, 2FA confirm, and the resolved seams
 *             table (webhook seams + the Cycle-18 wire seams)
 *   nonce   — issue + ONE-TIME consume probe (offline): --user=ID proves the
 *             replay guard end-to-end through the SAME registry the webhook
 *             uses
 *   flush   — clear("") whole-registry sweep of the nonce registry (the
 *             memory registry is per-process; the PSR-16 prefix sweep is
 *             wave-2 polish)
 *   import  — Cycle 18: the REAL Telegram login over the wire.
 *             `auth.importAuthorization#a57a7dad` turns a Telegram-exported
 *             authorization into a bound login. The authorization id + bytes
 *             are read from STDIN (or --file) as JSON
 *             `{"id":123,"bytes":"<base64>"}` — NEVER as command-line
 *             arguments, because the bytes are a credential and a shell
 *             argument lands in history and in `ps`. --user=ID names the
 *             Laravel user the login is for. A PASSWORD_REQUIRED refusal
 *             prompts for the 2FA password HIDDEN (never echoed, never an
 *             argument) and retries through the PasswordConfirmer.
 *
 * Every seam resolves from the container through the fixed
 * teleclient.passport.* keys (PassportSupport) — the same instances the
 * facade and Teleframe::passport() use.
 */
final class PassportCommand extends Command
{
    private const ACTIONS = ['status', 'nonce', 'flush', 'import'];

    protected $signature = 'teleframe:passport
        {action : status|nonce|flush|import}
        {--user= : nonce/import: the Laravel user id the probe/login is for}
        {--file= : import: read the exported authorization JSON from this file instead of STDIN}';

    protected $description = 'Telegram Passport login: status (config + seams), nonce (issue/consume probe), flush (clear the nonce registry), import (auth.importAuthorization wire login)';

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        if (! in_array($action, self::ACTIONS, true)) {
            $this->error("unknown action \"{$action}\" — expected ".implode('|', self::ACTIONS).'.');

            return self::FAILURE;
        }

        try {
            return match ($action) {
                'status' => $this->status(),
                'nonce' => $this->nonceProbe(),
                'flush' => $this->flush(),
                'import' => $this->import(),
            };
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function status(): int
    {
        /** @var Repository $config */
        $config = $this->getLaravel()->make('config');
        $values = new PassportConfig((array) ($config->get('teleframe.passport') ?? []));

        $this->info('Passport login: '.($values->enabled() ? 'ENABLED' : 'disabled (inert — configure teleframe.passport)'));
        $this->info('private key path: '.($values->privateKeyPath() === '' ? '(not configured)' : $values->privateKeyPath()).' — the PEM itself is never shown');
        $this->info('tenant account id: '.($values->primaryAccountId() === null ? '(unset → Identity primary-account rule)' : (string) $values->primaryAccountId()));
        $this->info('nonce TTL: '.$values->nonceTtl().'s'.($values->nonceStore() === '' ? ' (memory registry)' : ' (PSR-16 store: '.$values->nonceStore().')'));
        $this->info('allowed types: '.implode(', ', $values->allowedTypes()));
        // No field is printed because none is configured: in 'field' mode the
        // claim is INFERRED from the payload, and the console claiming to show a
        // field that nothing reads is worse than saying nothing.
        $this->info('user lookup: '.$values->userLookup().' (claim inferred from the payload)');
        $this->info('2FA confirm: '.($values->requirePasswordConfirmation() ? 'required' : 'off'));
        $this->table(['seam', 'resolved'], $this->seamRows());

        return self::SUCCESS;
    }

    private function nonceProbe(): int
    {
        $userId = (int) $this->option('user');
        if ($userId <= 0) {
            $this->error('nonce needs --user=N (a positive user id). Example: php artisan teleframe:passport nonce --user=7');

            return self::FAILURE;
        }

        $service = $this->service();
        $nonces = $this->nonces();
        $nonce = $service->issueNonce($userId);
        $consumed = $nonces->consume($nonce);
        $replay = $nonces->consume($nonce);

        if ($consumed !== $userId) {
            $this->error('nonce probe FAILED: consumed user '.var_export($consumed, true)." — expected {$userId}.");

            return self::FAILURE;
        }

        if ($replay !== null) {
            $this->error('nonce probe FAILED: the nonce consumed twice (replay guard broken).');

            return self::FAILURE;
        }

        // The nonce VALUE is never printed. It is a one-time 128-bit secret
        // whose leak is account takeover by this module's own threat model
        // (PassportLoginService:186-193) — and this probe has just consumed it,
        // so nothing observable depends on seeing it. The probe reports that
        // the round-trip held, and the length, which is what proves a full
        // 128-bit nonce was issued.
        $this->info('nonce probe ok: issued a '.strlen($nonce).'-char nonce for user '.$userId.' — consumed once, replay refused ('.($nonces instanceof MemoryNonceRegistry ? 'memory registry' : 'PSR-16 registry').').');

        return self::SUCCESS;
    }

    private function flush(): int
    {
        $this->nonces()->clear(''); // whole-registry sweep contract (the interface clear() is per-nonce; the PSR-16 prefix sweep is wave-2 polish)

        $this->info('Nonce registry flushed (clear("") sweep — the memory registry is per-process; the PSR-16 prefix sweep is wave-2 polish).');

        return self::SUCCESS;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function seamRows(): array
    {
        $rows = [];
        foreach (PassportSupport::seams() as $key) {
            try {
                $seam = $this->getLaravel()->make($key);
                $rows[] = [$key, is_object($seam) ? $seam::class : get_debug_type($seam)];
            } catch (\Throwable $e) {
                $rows[] = [$key, 'ERROR '.$e::class.': '.$e->getMessage()];
            }
        }

        return $rows;
    }

    /**
     * Cycle 18 `import`: the real wire login. `auth.importAuthorization#a57a7dad`
     * with the OFFICIAL id, the authorization id + bytes read from STDIN or
     * --file (never from argv — the bytes are a credential), --user=ID for the
     * Laravel user, and a HIDDEN 2FA prompt when Telegram answers
     * PASSWORD_REQUIRED.
     *
     * Nothing about the authorization is echoed: only the imported telegram
     * user id and the binding outcome are printed.
     */
    private function import(): int
    {
        $userId = (int) $this->option('user');
        if ($userId <= 0) {
            $this->error('import needs --user=N (the Laravel user id the login is for)');

            return self::FAILURE;
        }

        $user = $this->laravelUser($userId);
        if ($user === null) {
            $this->error("no Laravel user {$userId} resolves through the bound auth provider — import needs an identified user");

            return self::FAILURE;
        }

        try {
            $payload = $this->readAuthorizationPayload();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $id = $payload['id'];
        $bytes = $this->decodeBytes($payload['bytes']);
        if ($bytes === null) {
            $this->error('the authorization "bytes" are not valid base64 (refused — nothing dispatched)');

            return self::FAILURE;
        }

        try {
            $result = $this->wireLogin()->login($id, $bytes, $user, $this->passwordIfAsked());
        } catch (PasswordRequiredException $e) {
            // Telegram asked for the 2FA cloud password: ask for it HIDDEN and
            // retry through the same shipped PasswordConfirmer chain.
            $this->comment('telegram requires the 2FA cloud password for this passport call');
            $password = (string) $this->secret('2FA password');
            if ($password === '') {
                $this->error('no 2FA password given — the login stays refused (never a silent pass)');

                return self::FAILURE;
            }

            try {
                $result = $this->wireLogin()->login($id, $bytes, $user, $password);
            } catch (\Throwable $retry) {
                $this->error($retry->getMessage());

                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'passport import ok: telegram user %d bound to laravel user %d (account %s)',
            $result->tgUserId,
            $userId,
            $result->accountId === null ? 'unset' : (string) $result->accountId
        ));

        return self::SUCCESS;
    }

    /**
     * The exported authorization as {"id": int, "bytes": base64}. Read from
     * --file when given, else STDIN. The bytes are NEVER an option value.
     *
     * @return array{id: int, bytes: string}
     *
     * @throws RuntimeException on a missing/unreadable/unusable payload
     */
    private function readAuthorizationPayload(): array
    {
        $file = (string) ($this->option('file') ?? '');
        $raw = $file === '' ? (string) file_get_contents('php://stdin') : (string) @file_get_contents($file);

        if (trim($raw) === '') {
            throw new RuntimeException(
                'import needs the exported authorization JSON on STDIN'
                .' ({"id":123,"bytes":"<base64>"}), or --file=PATH — never as an argument'
            );
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('the authorization payload is not a JSON object');
        }

        $id = $decoded['id'] ?? null;
        $bytes = $decoded['bytes'] ?? null;

        if (! is_int($id) && ! (is_string($id) && ltrim($id, '0123456789') === '')) {
            throw new RuntimeException('the authorization payload needs a positive integer "id"');
        }

        if ((int) $id <= 0) {
            throw new RuntimeException('the authorization payload needs a positive integer "id"');
        }

        if (! is_string($bytes) || $bytes === '') {
            throw new RuntimeException('the authorization payload needs a non-empty base64 "bytes"');
        }

        return ['id' => (int) $id, 'bytes' => $bytes];
    }

    /** The base64 authorization bytes, or null when they do not decode. */
    private function decodeBytes(string $bytes): ?string
    {
        $decoded = base64_decode($bytes, true);

        return $decoded === false || $decoded === '' ? null : $decoded;
    }

    /** The 2FA password, or null when the operator declines to type one. */
    private function passwordIfAsked(): ?string
    {
        if (! $this->input->isInteractive()) {
            return null;
        }

        $password = (string) $this->secret('2FA password (blank to skip)');

        return $password === '' ? null : $password;
    }

    /**
     * The Laravel user for --user, via the host's own auth provider — the
     * package never owns user lookup (Cycle-8b). Null when nothing is bound, or
     * when the guard's provider cannot be reached (a guard without a user
     * provider is the host's shape, not our failure). A provider that THROWS
     * (a missing users table, a dead connection) is loud with its own reason
     * rather than a raw SQL dump — the operator has to be able to tell "no such
     * user" from "your auth provider is broken".
     */
    private function laravelUser(int $userId): ?Model
    {
        if (! $this->getLaravel()->bound('auth')) {
            return null;
        }

        try {
            $auth = $this->getLaravel()->make('auth');
            if (! $auth instanceof AuthFactory) {
                return null;
            }

            $guard = $auth->guard();
            if (! method_exists($guard, 'getProvider')) {
                return null;
            }

            $user = $guard->getProvider()->retrieveById($userId);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'the host auth provider could not resolve laravel user '.$userId.' — '.$e->getMessage(),
                0,
                $e
            );
        }

        return $user instanceof Model ? $user : null;
    }

    private function wireLogin(): PassportWireLogin
    {
        $login = $this->getLaravel()->make(PassportSupport::WIRE_LOGIN_KEY);
        if (! $login instanceof PassportWireLogin) {
            throw new RuntimeException(PassportSupport::WIRE_LOGIN_KEY.' must bind a '.PassportWireLogin::class.'.');
        }

        return $login;
    }

    private function service(): PassportLoginService
    {
        $service = $this->getLaravel()->make(PassportSupport::SERVICE_KEY);
        if (! $service instanceof PassportLoginService) {
            throw new RuntimeException(PassportSupport::SERVICE_KEY.' must bind a '.PassportLoginService::class.'.');
        }

        return $service;
    }

    private function nonces(): NonceRegistry
    {
        $nonces = $this->getLaravel()->make(PassportSupport::NONCES_KEY);
        if (! $nonces instanceof NonceRegistry) {
            throw new RuntimeException(PassportSupport::NONCES_KEY.' must bind a '.NonceRegistry::class.'.');
        }

        return $nonces;
    }
}
