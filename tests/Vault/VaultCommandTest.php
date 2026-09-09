<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Vault;

use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;

final class VaultCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [VaultTestServiceProvider::class];
    }

    public function test_add_app_add_account_list_round_trip(): void
    {
        $this->artisan('teleframe:vault-add-app', [
            'label' => 'teleframe',
            '--api-id' => 12345,
            '--api-hash' => str_repeat('h', 32),
        ])->assertOk();
        self::assertNotNull(TelegramApp::query()->where('label', 'teleframe')->first());

        $this->artisan('teleframe:vault-add-account', [
            'label' => 'main',
            '--app' => 'teleframe',
            '--type' => 'user',
        ])->assertOk();
        $account = TelegramAccount::query()->where('label', 'main')->first();
        self::assertNotNull($account);
        self::assertSame('user', $account->type);

        $this->artisan('teleframe:vault-list')
            ->expectsTable(['Label', 'api_id'], [['teleframe', '12345']])
            ->expectsTable(['Label', 'Type', 'app_id', 'dc_id'], [['main', 'user', '1', '2']])
            ->assertOk();
    }
}
