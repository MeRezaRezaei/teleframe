<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Vault;

use Illuminate\Support\Facades\Schema;

final class VaultMigrationTest extends TestCase
{
    public function test_vault_tables_exist(): void
    {
        self::assertTrue(Schema::hasTable('telegram_apps'));
        self::assertTrue(Schema::hasTable('telegram_accounts'));
    }
}
