<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use RuntimeException;
use Throwable;
use TypeError;

/**
 * Live health check: TCP + auth-key handshake + help.getNearestDc
 * (optionally bot MTProto login, optionally a STORED account session)
 * plus a bounded Redis-bus probe.
 *
 * Two honesty rules this command exists to enforce:
 * - With no `--account` it probes a THROWAWAY key, which can never detect a
 *   revoked session. `--account=<id>` probes the stored auth key instead, so
 *   a terminated/revoked session surfaces as the real AuthKeyException.
 * - The bus probe names the consumer group explicitly: a missing group is
 *   what silently starves `teleframe:ingest`, and it is a FINDING, not a
 *   hard failure, because the bus may legitimately not be running.
 */
class DoctorCommand extends Command
{
    protected $signature = 'teleframe:doctor
                            {--bot : Also verify bot MTProto authorization}
                            {--dc=2 : Target Telegram Data Center ID (1-5)}
                            {--account= : Probe the STORED session of a telegram_accounts.id instead of a throwaway key}
                            {--bus-timeout=3 : Seconds allowed for each Redis call in the bus probe}';

    protected $description = 'Verify Teleframe live MTProto connectivity (no account needed)';

    /** Seconds a single Redis call may take before it is declared hung. */
    private const BUS_CALL_TIMEOUT = 3.0;

    public function handle(): int
    {
        $dcId = (int) $this->option('dc');
        $apiId = (int) (config('teleframe.api_id') ?: 0);
        $apiHash = (string) (config('teleframe.api_hash') ?: '');

        if ($apiId === 0 || $apiHash === '') {
            if (! $this->input->isInteractive()) {
                $this->components->error(
                    'teleframe:doctor needs TELEGRAM_API_ID and TELEGRAM_API_HASH '
                    .'(see config/teleframe.php, or publish it with '
                    .'`php artisan vendor:publish --tag=teleframe-config`). Not set.'
                );

                return self::FAILURE;
            }

            $apiId = $apiId ?: (int) $this->ask('Telegram API id');
            $apiHash = $apiHash !== '' ? $apiHash : (string) $this->ask('Telegram API hash');
        }

        $label = 'handshake+getNearestDc';
        $accountId = $this->option('account');

        if (is_string($accountId) && $accountId !== '') {
            try {
                $account = TelegramAccount::query()->find((int) $accountId);
            } catch (Throwable $e) {
                // A missing/denied vault table is a hard answer, not a crash.
                $this->components->error('Cannot read telegram_accounts — '.$e->getMessage());

                return self::FAILURE;
            }

            if (! $account instanceof TelegramAccount) {
                $this->components->error("No telegram_accounts row with id {$accountId}.");

                return self::FAILURE;
            }

            $stored = (string) ($account->getAttribute('session') ?? '');
            if ($stored === '') {
                $this->components->error(
                    "Account {$accountId} has no stored session string, so there is nothing to probe."
                );

                return self::FAILURE;
            }

            $credentials = $account->credentials();
            $apiId = $credentials['api_id'] ?? $apiId;
            $apiHash = ($credentials['api_hash'] ?? '') !== '' ? (string) $credentials['api_hash'] : $apiHash;

            try {
                $client = $this->clientForStoredSession($stored, (int) $apiId, (string) $apiHash);
            } catch (Throwable $e) {
                $this->components->error("Account {$accountId} stored session is unreadable — ".$e->getMessage());

                return self::FAILURE;
            }

            // The stored session's own DC is authoritative: probing a stored
            // auth key against the wrong DC reports a misleading failure.
            $dcId = $client->session instanceof SessionData ? $client->session->dcId : $dcId;
            $label = "stored session account={$accountId} dc={$dcId}";
        } else {
            $client = (new Client(
                apiId: $apiId,
                apiHash: $apiHash,
                session: new SessionData(dcId: $dcId, authKey: ''),
            ))->live();
        }

        $exit = $this->probeConnectivity(
            $client,
            Client::DC_IPS[$dcId] ?? Client::DC_IPS[2],
            Client::DEFAULT_PORT,
            $label,
        );

        $busExit = $this->probeBusIfConfigured();
        if ($busExit === 1) {
            // An unreachable bus is provably dead ingest; letting the MTProto
            // probe's success speak for the whole install is the exact lie
            // this command exists to prevent.
            $exit = 1;
        }

        if ($exit === 0 && $this->option('bot')) {
            $token = (string) (config('teleframe.bot_token') ?: '');
            if ($token === '') {
                if (! $this->input->isInteractive()) {
                    $this->components->error('teleframe:doctor --bot needs TELEGRAM_BOT_TOKEN. Not set.');

                    return self::FAILURE;
                }

                $token = (string) $this->ask('Bot token');
            }

            $exit = $this->probeBotAuth($apiId, $apiHash, $token, $dcId);
        }

        return $exit;
    }

    /**
     * Must stay runnable on a bare command (no Laravel app): uses only
     * line()/error(), never $this->components or $this->option().
     *
     * `$label` names WHAT was probed, so a stored-session run can never be
     * mistaken for the throwaway-key run it sits next to.
     */
    public function probeConnectivity(Client $client, string $host, int $port, string $label = 'handshake+getNearestDc'): int
    {
        $t0 = microtime(true);
        try {
            $result = $client->callToHost($host, $port);
            $ms = (int) ((microtime(true) - $t0) * 1000);
            $this->line("<info>OK {$label} {$host}:{$port} in {$ms}ms</info>");
            if (isset($result['this_dc'], $result['nearest_dc'])) {
                $this->line("<info>This DC {$result['this_dc']}, nearest DC {$result['nearest_dc']}</info>");
            }

            return 0;
        } catch (Throwable $e) {
            $this->line("<error>FAIL {$label} {$host}:{$port} — ".$e->getMessage().'</error>');

            return 1;
        }
    }

    /**
     * Import a STORED session string (telegram_accounts.session, decrypted by
     * the model's `encrypted` cast) and return a live client bound to it.
     *
     * This is the whole point of `--account`: a blank auth key makes
     * Client::ensureAuthKey() run a FRESH handshake, so a revoked/terminated
     * session is indistinguishable from a healthy one. With the stored key the
     * probe is an authorization check in the only sense that matters.
     *
     * @throws Throwable when the stored string is not an importable session
     */
    public function clientForStoredSession(string $stored, int $apiId, string $apiHash): Client
    {
        $session = SessionData::importString($stored);

        if (strlen($session->authKey) !== 256) {
            throw new RuntimeException(
                'Stored session does not carry a 256-byte auth key (got '.strlen($session->authKey)
                .' bytes) — re-run teleframe:login for this account.'
            );
        }

        return (new Client(apiId: $apiId, apiHash: $apiHash, session: $session))->live();
    }

    /**
     * Run the bus probe when the app actually has a bus connection bound.
     *
     * Prints nothing and returns 0 when there is no container (bare command)
     * or no binding: the pre-existing no-app behaviour must not change.
     */
    public function probeBusIfConfigured(): int
    {
        // The global container, not $this->laravel: a bare command (and the
        // no-app path this command has always supported) has no application
        // bound, and getInstance() then simply reports nothing as bound.
        $container = Container::getInstance();

        if (! $container->bound(RedisConnectionContract::class)) {
            // SKIPPED is not PASSED. Returning 0 here told the operator the bus
            // was healthy on a host where nothing about the bus was checked at
            // all — the one job this command exists to do.
            // warn() not components->warn(): the bare-command path has no
            // components view, and a null deref while REPORTING a problem is
            // the worst possible time to discover that.
            $this->warn('Bus probe SKIPPED: no Redis connection is bound. Checked nothing about the bus.');

            return 1;
        }

        $redis = $container->make(RedisConnectionContract::class);

        $timeout = (float) ($this->option('bus-timeout') ?: 0) ?: self::BUS_CALL_TIMEOUT;

        try {
            return $this->probeBus($redis, $timeout > 0 ? $timeout : self::BUS_CALL_TIMEOUT);
        } catch (TypeError $e) {
            // A host app bound this contract to something unusable: that is a
            // broken bus, not a healthy install, so it must not read as OK.
            $this->line('<error>FAIL bus — unusable binding: '.$e->getMessage().'</error>');

            return 1;
        }
    }

    /**
     * Bounded Redis/bus probe, in this order: PING, XLEN <stream>,
     * XINFO GROUPS <stream> (is the consumer group there?), XLEN <dead-letter>.
     *
     * Every call is wrapped in an alarm-guarded `bounded()` so a hung or
     * half-open Redis connection cannot hang the doctor — the single most
     * likely way this command gets wedged in production.
     *
     * Exit code: 0 when Redis answered (a missing consumer group is a
     * FINDING, not a failure — the bus may be down between runs), 1 when
     * Redis is unreachable or hung, because then ingest is provably dead.
     */
    public function probeBus(RedisConnectionContract $redis, float $timeout = self::BUS_CALL_TIMEOUT): int
    {
        try {
            $this->bounded($timeout, static fn (): mixed => $redis->command('ping'));
            $this->line('<info>OK bus PING</info>');
        } catch (Throwable $e) {
            $this->line('<error>FAIL bus PING — '.$e->getMessage().'</error>');

            return 1;
        }

        try {
            $length = $this->bounded($timeout, static fn (): int => $redis->llen(StreamSchema::STREAM));
            $this->line('<info>OK bus XLEN '.StreamSchema::STREAM." = {$length}</info>");
        } catch (Throwable $e) {
            $this->line('<error>FAIL bus XLEN '.StreamSchema::STREAM.' — '.$e->getMessage().'</error>');

            return 1;
        }

        try {
            /** @var array<int, mixed> $groups */
            $groups = (array) $this->bounded(
                $timeout,
                static fn (): mixed => $redis->command('xinfo', ['groups', StreamSchema::STREAM]),
            );
            $this->reportGroups($groups);
        } catch (Throwable $e) {
            // A stream that does not exist yet, or a hung XINFO: report the
            // missing group explicitly — silence here reads as "healthy".
            $this->line(
                '<comment>FINDING bus XINFO GROUPS '.StreamSchema::STREAM
                .' — consumer group '.StreamSchema::GROUP.' NOT CONFIRMED: '.$e->getMessage().'</comment>'
            );

            return 0;
        }

        try {
            $dead = $this->bounded($timeout, static fn (): int => $redis->llen(StreamSchema::DL));
            if ($dead > 0) {
                $this->line('<comment>FINDING bus XLEN '.StreamSchema::DL." = {$dead} (unconsumed dead-letter entries)</comment>");
            } else {
                $this->line('<info>OK bus XLEN '.StreamSchema::DL." = {$dead}</info>");
            }
        } catch (Throwable $e) {
            $this->line('<error>FAIL bus XLEN '.StreamSchema::DL.' — '.$e->getMessage().'</error>');

            return 1;
        }

        return 0;
    }

    /**
     * Report which consumer groups exist, and call out the one that matters.
     *
     * The reply shape differs per driver (predis RESP2 returns a positional
     * list, phpredis a map keyed by group name), so the group NAME is matched
     * by value rather than by position.
     *
     * @param  array<int|string, mixed>  $groups
     */
    private function reportGroups(array $groups): void
    {
        $found = false;
        foreach ($groups as $name => $meta) {
            $candidate = is_string($name) ? $name : (is_array($meta) && isset($meta['name']) ? (string) $meta['name'] : '');
            if ($candidate === '' && is_array($meta) && isset($meta[1]) && is_string($meta[1])) {
                // predis RESP2: [name, consumers, pending, lastDeliveredId, ...]
                $candidate = $meta[1];
            }
            if ($candidate === StreamSchema::GROUP) {
                $found = true;
            }
        }

        if ($found) {
            $this->line(
                '<info>OK bus XINFO GROUPS '.StreamSchema::STREAM
                .' — consumer group '.StreamSchema::GROUP.' present</info>'
            );

            return;
        }

        $this->line(
            '<comment>FINDING bus XINFO GROUPS '.StreamSchema::STREAM
            .' — consumer group '.StreamSchema::GROUP
            .' is ABSENT; XREADGROUP on '.StreamSchema::STREAM
            .' will fail and teleframe:ingest will consume nothing until the group is created.</comment>'
        );
    }

    /**
     * Run `$op` under an alarm, so a call that blocks past `$seconds` is
     * abandoned and reported instead of wedging the whole command.
     *
     * pcntl is optional (as everywhere in this package): without it the call
     * runs unbounded, which is the pre-existing behaviour of every other
     * console command in the package.
     *
     * @template T
     *
     * @param  callable(): T  $op
     * @return T
     *
     * @throws RuntimeException when the alarm fired first
     */
    private function bounded(float $seconds, callable $op): mixed
    {
        if ($seconds <= 0
            || ! function_exists('pcntl_alarm')
            || ! function_exists('pcntl_signal')
            || ! function_exists('pcntl_async_signals')
            || ! function_exists('pcntl_get_last_error')) {
            return $op();
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGALRM, static function () use ($seconds): void {
            throw new RuntimeException('timed out after '.$seconds.'s');
        });
        pcntl_alarm((int) ceil($seconds));

        try {
            return $op();
        } finally {
            pcntl_alarm(0);
            pcntl_signal(SIGALRM, SIG_DFL);
        }
    }

    protected function probeBotAuth(int $apiId, string $apiHash, string $token, int $dcId): int
    {
        try {
            $auth = app(TeleframeAuthService::class);
            $auth->loginBot($token, $apiId, $apiHash, $dcId);
            $this->components->info('OK bot MTProto authorization (session generated)');

            return 0;
        } catch (Throwable $e) {
            $this->components->error('Bot MTProto login failed — '.$e->getMessage());

            return 1;
        }
    }
}
