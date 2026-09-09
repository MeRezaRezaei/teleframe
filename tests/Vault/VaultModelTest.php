<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Vault;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;

final class VaultModelTest extends TestCase
{
    public function test_api_hash_stored_encrypted_and_relation_resolves(): void
    {
        $app = TelegramApp::query()->create([
            'label' => 'teleframe',
            'api_id' => 12345,
            'api_hash' => 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5',
        ]);

        $raw = DB::table('telegram_apps')->where('id', $app->id)->value('api_hash');
        self::assertNotSame('a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5', $raw);
        self::assertSame('a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5', $app->fresh()->api_hash);

        $account = TelegramAccount::query()->create([
            'app_id' => $app->id,
            'label' => 'main',
            'type' => 'user',
            'session' => 'my-session-string',
            'dc_id' => 2,
        ]);

        self::assertSame($app->id, $account->app->id);
        self::assertSame('my-session-string', $account->fresh()->session);
        $rawSession = DB::table('telegram_accounts')->where('id', $account->id)->value('session');
        self::assertNotSame('my-session-string', $rawSession);
    }

    public function test_user_requires_app_bot_http_allows_null_app(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TelegramAccount::query()->create([
            'app_id' => null,
            'label' => 'orphan-user',
            'type' => 'user',
        ]);
    }

    public function test_bot_http_without_app_is_allowed(): void
    {
        $bot = TelegramAccount::query()->create([
            'app_id' => null,
            'label' => 'my-bot',
            'type' => 'bot',
            'bot_token' => '123:ABC',
        ]);

        self::assertSame('123:ABC', $bot->fresh()->bot_token);
    }
}
