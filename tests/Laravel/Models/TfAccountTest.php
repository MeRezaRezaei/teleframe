<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Models;

use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Laravel\Models\TfAccount;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * TfAccount — the DB-backed runtime account registry model: telegram
 * account_id as the non-incrementing primary key, kind='account'|'bot',
 * mutable casts (enabled bool, dc_id int, nullable port int), and an
 * OPAQUE session_ref (never a credential). Also proves the model boots and
 * saves cleanly when no bus redis is bound (best-effort observer wiring).
 */
final class TfAccountTest extends TestbenchTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 3).'/src/Laravel/Migrations/2026_09_22_000100_create_tf_accounts_table.php',
            '--realpath' => true,
        ])->assertExitCode(0);
    }

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 3);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    public function test_telegram_account_id_is_the_non_incrementing_primary_key(): void
    {
        $account = new TfAccount([
            'id' => 501558149,
            'label' => 'company-main',
            'dc_id' => 2,
            'kind' => TfAccount::KIND_ACCOUNT,
            'session_ref' => 'vault:company-main',
            'enabled' => true,
        ]);
        $account->save();

        self::assertFalse($account->incrementing, 'telegram account_id is not auto-increment');
        self::assertSame('int', $account->getKeyType());
        self::assertSame('id', $account->getKeyName());
        self::assertSame(501558149, $account->getKey());
        self::assertSame(501558149, $account->id);

        $fresh = TfAccount::query()->find(501558149);
        self::assertNotNull($fresh);
        self::assertSame(501558149, $fresh->id);
    }

    public function test_casts_are_applied_on_round_trip(): void
    {
        $account = new TfAccount([
            'id' => 1001,
            'label' => 'casted',
            'dc_id' => 4,
            'port' => 8443,
            'kind' => TfAccount::KIND_ACCOUNT,
            'session_ref' => 'vault:casted',
            'enabled' => true,
        ]);
        $account->save();

        $stored = TfAccount::query()->find(1001);

        self::assertTrue($stored->enabled, 'enabled stored as bool');
        self::assertSame(4, $stored->dc_id, 'dc_id stored as int');
        self::assertSame(8443, $stored->port, 'port stored as int');

        $stored->enabled = false;
        $stored->port = null;
        $stored->save();

        $toggled = TfAccount::query()->find(1001);
        self::assertFalse($toggled->enabled);
        self::assertNull($toggled->port, 'nullable port round-trips to null');
    }

    public function test_kind_and_helpers(): void
    {
        $account = new TfAccount([
            'id' => 7,
            'label' => 'plain',
            'dc_id' => 2,
            'kind' => TfAccount::KIND_ACCOUNT,
            'session_ref' => 'vault:plain',
            'enabled' => true,
        ]);
        $bot = new TfAccount([
            'id' => 8,
            'label' => 'bot',
            'dc_id' => 2,
            'kind' => TfAccount::KIND_BOT,
            'session_ref' => 'vault:bot',
            'enabled' => true,
        ]);

        // @phpstan-ignore-next-line docblock type of un-saved attribute
        self::assertSame('account', $account->kind);
        self::assertFalse($account->isBot());
        // @phpstan-ignore-next-line docblock type of un-saved attribute
        self::assertSame('bot', $bot->kind);
        self::assertTrue($bot->isBot());
    }

    public function test_fillable_is_scoped_and_session_ref_stays_opaque(): void
    {
        $account = new TfAccount([
            'id' => 9,
            'label' => 'scoped',
            'dc_id' => 2,
            'kind' => TfAccount::KIND_ACCOUNT,
            'session_ref' => 'vault:scoped',
            'enabled' => true,
        ]);

        self::assertSame('vault:scoped', $account->session_ref, 'session_ref is an opaque reference');
        self::assertTrue($account->isFillable('session_ref'));

        $account->fill(['hacked' => 'nope']);
        self::assertArrayNotHasKey('hacked', $account->getAttributes());
    }

    public function test_save_works_without_a_bus_redis_bound(): void
    {
        // The provider binds RedisConnectionContract to a factory that throws
        // when no illuminate redis service exists. The booted observer must
        // degrade to "events only" — the account write itself must succeed.
        self::assertTrue(app()->bound(RedisConnectionContract::class));

        $account = new TfAccount([
            'id' => 10,
            'label' => 'no-bus',
            'dc_id' => 2,
            'kind' => TfAccount::KIND_ACCOUNT,
            'session_ref' => 'vault:no-bus',
            'enabled' => true,
        ]);

        $account->save();

        self::assertSame(10, TfAccount::query()->find(10)?->id);
        self::assertSame('no-bus', (string) TfAccount::query()->find(10)?->label);
    }
}
