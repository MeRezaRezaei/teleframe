<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\AuthKeyException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultListCommand;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramSessionRevoked;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;
use MeRezaRezaei\Teleframe\Vault\Vault;
use RuntimeException;
use Spatie\LaravelData\LaravelDataServiceProvider;

/**
 * S1-S4: a revoked session must have an OWNER.
 *
 * Before this, an AuthKeyException was an anonymous error: the vault row kept
 * its session blob, kept decrypting, and kept being served, so nothing in the
 * package could say which accounts need re-login. These tests pin the four
 * properties that make it answerable:
 *
 *  - the poller records the revocation (revoked_at) and announces it once,
 *    while a healthy account is left untouched;
 *  - the read path REFUSES a revoked account by name and still serves a live one;
 *  - `teleframe:vault-list` shows the revoked account as revoked;
 *  - errorLoopAction still returns 'rethrow' for AuthKeyException (f5a1db08).
 *
 * Real sqlite :memory: through the package's own vault migrations (same
 * pattern as tests/Vault/TestCase) — no external DB, no credentials, no
 * network, no real session string (the fake scope throws before any socket).
 */
final class SessionRevocationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        // The `encrypted` cast on session/bot_token needs a key. A throwaway
        // in-memory key: not a credential, never written anywhere.
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('v', 32)));
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            TeleframeServiceProvider::class,
            SessionRevocationConsoleProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $root = dirname(__DIR__, 2).'/src/Laravel/Migrations';
        (require $root.'/2026_09_09_000200_create_telegram_apps_table.php')->up();
        (require $root.'/2026_09_09_000201_create_telegram_accounts_table.php')->up();
        (require $root.'/2026_09_14_000000_add_user_id_to_telegram_accounts_table.php')->up();
        (require $root.'/2026_09_27_000100_add_revoked_at_to_telegram_accounts_table.php')->up();
    }

    // ------------------------------------------------------------------
    // S1 — the revocation is RECORDED, and only on the guilty account
    // ------------------------------------------------------------------

    public function test_auth_key_exception_stamps_revoked_at_on_that_account_only(): void
    {
        $dead = $this->makeAccount('dead-one', 501558149);
        $healthy = $this->makeAccount('live-one', 501558150);

        $announced = [];
        $this->app['events']->listen(
            TelegramSessionRevoked::class,
            static function (TelegramSessionRevoked $e) use (&$announced): void {
                $announced[] = $e->accountId;
            },
        );

        $poller = new UpdatePollerService;
        $rethrown = null;
        try {
            // Bounded: the fake scope throws on its FIRST call, and the
            // rethrow leaves pollUser immediately. No loop, no sleep.
            $poller->pollUser($this->deadScope(501558149));
        } catch (AuthKeyException $e) {
            $rethrown = $e;
        }

        self::assertInstanceOf(
            AuthKeyException::class,
            $rethrown,
            'f5a1db08: a rejected auth key must still escape the loop',
        );

        $dead->refresh();
        $healthy->refresh();

        self::assertNotNull($dead->revoked_at, 'S1: the rejected account must be stamped revoked');
        self::assertNull($healthy->revoked_at, 'S1: a healthy account must stay unrevoked');

        // The stamp is the durable truth; the event is the notification. The
        // provider listener (booted above) is what performs the stamp.
        self::assertSame([501558149], $announced, 'S1: exactly the dead account is announced');
    }

    public function test_second_revocation_is_idempotent_and_does_not_re_stamp(): void
    {
        $account = $this->makeAccount('dead-twice', 501558151);

        self::assertTrue($account->markRevoked(), 'the FIRST stamp is the one that records the revocation');
        $firstStamp = $account->revoked_at?->getTimestamp();

        self::assertFalse($account->markRevoked(), 'a repeat must not re-stamp');
        $account->refresh();
        self::assertSame($firstStamp, $account->revoked_at?->getTimestamp());
        self::assertNull(TelegramAccount::markRevokedFor(501558151), 'an already-known revocation reports nothing new');
    }

    public function test_a_healthy_poll_announces_nothing_and_stamps_nothing(): void
    {
        $account = $this->makeAccount('still-fine', 501558152);

        $announced = [];
        $this->app['events']->listen(
            TelegramSessionRevoked::class,
            static function (TelegramSessionRevoked $e) use (&$announced): void {
                $announced[] = $e->accountId;
            },
        );

        $poller = new UpdatePollerService;
        // Bounded: the scope answers ONE difference, then stops the loop.
        $poller->pollUser($this->healthyScope($poller, 501558152));

        self::assertSame([], $announced, 'a live key must announce nothing');

        $account->refresh();
        self::assertNull($account->revoked_at, 'a live key must not be stamped');
        self::assertFalse($account->isRevoked());
    }

    // ------------------------------------------------------------------
    // S2 — the read path refuses a revoked account BY NAME
    // ------------------------------------------------------------------

    public function test_resolve_vault_account_refuses_a_revoked_account_and_serves_a_live_one(): void
    {
        $dead = $this->makeAccount('corp-main', 501558149);
        $live = $this->makeAccount('corp-backup', 501558150);
        $dead->markRevoked();

        $client = $this->vaultClient();

        // By label — the call the host actually makes.
        try {
            $client->userFromVault('corp-main');
            self::fail('S2: a revoked account must not be served');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('revoked', $e->getMessage());
            self::assertStringContainsString('re-login', $e->getMessage());
            self::assertStringContainsString('corp-main', $e->getMessage());
            self::assertStringNotContainsStringIgnoringCase('authkey', $e->getMessage());
        }

        // By Telegram id — the canonical identity, same refusal.
        try {
            $client->userFromVault(501558149);
            self::fail('S2: a revoked account must not be served by id either');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('re-login', $e->getMessage());
        }

        // A healthy account is still served, untouched.
        $scope = $client->userFromVault('corp-backup');
        self::assertInstanceOf(UserAccountScope::class, $scope);
        self::assertSame(501558150, $scope->getSession()->userId);
    }

    // ------------------------------------------------------------------
    // S3 — the operator's listing shows it
    // ------------------------------------------------------------------

    public function test_vault_list_shows_a_revoked_account_as_revoked(): void
    {
        $live = $this->makeAccount('alive', 501558150);
        $dead = $this->makeAccount('deceased', 501558149);
        $dead->markRevoked();

        $this->artisan('teleframe:vault-list')
            ->expectsTable(['Label', 'Type', 'Telegram ID', 'app_id', 'dc_id'], [
                ['alive', 'user', '501558150', (string) $live->app_id, '2'],
                ['deceased', 'user (revoked)', '501558149', (string) $dead->app_id, '2'],
            ])
            ->assertOk();
    }

    // ------------------------------------------------------------------
    // f5a1db08 must not regress
    // ------------------------------------------------------------------

    public function test_error_loop_action_still_rethrows_for_auth_key_exception(): void
    {
        $poller = new class extends UpdatePollerService
        {
            public function actionFor(\Throwable $e): string
            {
                return $this->errorLoopAction($e);
            }
        };

        self::assertSame('rethrow', $poller->actionFor(new AuthKeyException('SESSION_REVOKED')));
        self::assertSame('rethrow', $poller->actionFor(new AuthKeyException('AUTH_KEY_UNREGISTERED')));
        // A transient error is still only a backoff — the terminal verdict is
        // scoped to the auth key, not to every error.
        self::assertSame('backoff', $poller->actionFor(new RuntimeException('transient')));
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------

    private function makeAccount(string $label, int $telegramUserId): TelegramAccount
    {
        $app = TelegramApp::query()->first() ?? $this->makeApp();

        /** @var TelegramAccount $account */
        $account = TelegramAccount::query()->create([
            'label' => $label,
            'type' => TelegramAccount::TYPE_USER,
            'app_id' => $app->getAttribute('id'),
            'user_id' => $telegramUserId,
        ]);

        return $account;
    }

    private function makeApp(): TelegramApp
    {
        /** @var TelegramApp $app */
        $app = TelegramApp::query()->create([
            'label' => 'teleframe',
            'api_id' => 12345,
            'api_hash' => str_repeat('h', 32),
        ]);

        return $app;
    }

    private function vaultClient(): TeleframeClient
    {
        // Explicit vault binding: the refusal must not depend on a container
        // lookup order, it is a property of resolveVaultAccount().
        return new TeleframeClient(vault: $this->app->make(Vault::class));
    }

    /**
     * A user scope whose FIRST call rejects the auth key. Bounded by
     * construction: it throws before any transport work, and pollUser
     * rethrows, so exactly one call happens.
     */
    private function deadScope(int $telegramUserId): UserAccountScope
    {
        return new class(new MTProtoClient(apiId: 1, apiHash: 'hash', live: false), new SessionData(dcId: 2, authKey: random_bytes(256), userId: $telegramUserId)) extends UserAccountScope
        {
            public function call(string $method, array $params = []): array
            {
                throw new AuthKeyException('SESSION_REVOKED', 401, $method);
            }
        };
    }

    /**
     * A scope with a WORKING key: it answers one updates.differenceEmpty, and
     * the second call stops the poller and raises a non-auth error, so the
     * loop exits on its own. Exactly two calls, no sleep loop.
     */
    private function healthyScope(UpdatePollerService $poller, int $telegramUserId): UserAccountScope
    {
        return new class(new MTProtoClient(apiId: 1, apiHash: 'hash', live: false), new SessionData(dcId: 2, authKey: random_bytes(256), userId: $telegramUserId), $poller) extends UserAccountScope
        {
            private int $calls = 0;

            public function __construct(MTProtoClient $mtproto, SessionData $session, private UpdatePollerService $poller)
            {
                parent::__construct($mtproto, $session);
            }

            public function call(string $method, array $params = []): array
            {
                if ($this->calls++ === 0) {
                    return ['_' => 'updates.differenceEmpty', 'pts' => 10, 'date' => 20, 'qts' => 0, 'seq' => 0];
                }

                $this->poller->stop();

                throw new RuntimeException('halt polling');
            }
        };
    }
}

/**
 * Registers teleframe:vault-list for this test's app only.
 */
final class SessionRevocationConsoleProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([VaultListCommand::class]);
    }
}
