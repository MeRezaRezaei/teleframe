<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Vault;

use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;
use MeRezaRezaei\Teleframe\Vault\Vault;

final class VaultResolverTest extends TestCase
{
    public function test_resolve_by_label_exact_match_and_default_chain(): void
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
        TelegramAccount::query()->create([
            'app_id' => null,
            'label' => 'my-bot',
            'type' => 'bot',
            'bot_token' => '123:ABC',
        ]);

        $vault = new Vault();
        self::assertSame('main', $vault->account('main')?->label);
        self::assertSame('my-bot', $vault->account('my-bot')?->label);
        self::assertNull($vault->account('nope'));

        config(['teleframe.vault.default_account' => 'my-bot']);
        self::assertSame('my-bot', $vault->defaultAccount()?->label);
    }
}
