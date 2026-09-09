<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Vault;

use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;
use MeRezaRezaei\Teleframe\Vault\Vault;

final class VaultClientTest extends TestCase
{
    public function test_user_from_vault_builds_scope_with_row_credentials(): void
    {
        $app = TelegramApp::query()->create([
            'label' => 'teleframe',
            'api_id' => 12345,
            'api_hash' => str_repeat('h', 32),
        ]);
        TelegramAccount::query()->create([
            'app_id' => $app->id,
            'label' => 'main',
            'type' => 'user',
            'session' => 'sess-main',
        ]);

        $client = new TeleframeClient(vault: new Vault());
        $scope = $client->userFromVault('main');

        self::assertSame(12345, $scope->mtproto->apiId);
        self::assertSame('sess-main', $scope->session->authKey);
    }

    public function test_bot_from_vault_http_uses_token_only(): void
    {
        TelegramAccount::query()->create([
            'app_id' => null,
            'label' => 'my-bot',
            'type' => 'bot',
            'bot_token' => '123:ABC',
        ]);

        $client = new TeleframeClient(vault: new Vault());
        $bot = $client->botFromVault('my-bot');

        self::assertSame('123:ABC', $bot->botToken);
    }
}
