<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\MessagesDeleted;
use MeRezaRezaei\Teleframe\Ingest\SafeDelete;

/**
 * Phase C re-baseline: explicit-safe message deletion on the CURATED dial.
 * tf_messages is keyed (account_id, id) with NO extracted message_id /
 * constructor_id / tl_data / created_at legacy surface — the Telegram
 * message id IS the `id` column, and deletions match on it exactly (the
 * SafeDelete src seam was aligned in the same re-baseline).
 */
class SafeDeleteTest extends IngestTestCase
{
    private const ACCOUNT = 1;

    private SafeDelete $safeDelete;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([MessagesDeleted::class]);
        $this->safeDelete = new SafeDelete(app('events'));

        /*
         * WHY THIS SEEDS `user_id` AND NOT `id`
         *
         * Every tenant-bearing `tf_*` table carries a foreign key from its
         * `account_id` column to `telegram_accounts.user_id` — the migration's
         * `tenantLink()` (`2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php:386`)
         * names the leg `TENANT_LEG = 'account_id'` and the parent column
         * `TENANT_KEY = 'user_id'`, and `telegram_accounts_user_id_unique` is what
         * makes it referencable. So `account_id` is the caller's TELEGRAM user id,
         * never the vault row's own autoincrement `id`.
         *
         * This seed used to write `'id' => self::ACCOUNT`, which satisfies
         * nothing: it filled a column no FK reads, so the parent row existed and
         * the tenant FK still failed. That is why these three tests ran green for
         * as long as the harness left `PRAGMA foreign_keys` at 0 (see
         * `docs/decisions.md` D-022) — and they failed the moment enforcement was
         * turned on, which is the whole point of turning it on.
         *
         * The KEY was wrong; the VALUE never was. `self::ACCOUNT` is the tenant id
         * these tests write `tf_messages.account_id => 1` under, so it belongs on
         * `user_id` and the assertion values stay exactly as they are.
         *
         * This is a FIXTURE BUG, NOT A `src/` DEFECT — settled by shipped code:
         * `src/Laravel/MirrorIngesterFactory.php:160` writes the caller's Telegram
         * id straight into `account_id`; `src/Laravel/Services/TeleframeClient.php:335`
         * `resolveVaultAccount()` looks the parent up via `$vault->accountByUserId($userId)`
         * (`:357`), so the parent row is resolved BY `user_id` and therefore always
         * exists first; and `src/Laravel/Console/VaultAddAccountCommand.php:80` writes
         * `user_id` when an account is added, long before any update is ingested.
         * A test writing `tf_*` rows under an unplanted `user_id` describes a state the
         * ingest surface cannot reach. This file owns its tenant seed explicitly and so
         * deliberately does NOT override `ingestTenantIds()` — declaring account 1 in two
         * places would be the duplication, and the explicit seed here is the single
         * source of truth.
         */
        DB::table('telegram_accounts')->insert([
            'user_id' => self::ACCOUNT,
            'label' => 'safe-delete-'.self::ACCOUNT,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    public function test_delete_messages_removes_matching_rows(): void
    {
        DB::table('tf_messages')->insert([
            ['account_id' => 1, 'id' => 10, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 1001, 'date' => 1724852400, 'message' => 'a'],
            ['account_id' => 1, 'id' => 20, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 1001, 'date' => 1724852401, 'message' => 'b'],
            ['account_id' => 1, 'id' => 30, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 1001, 'date' => 1724852402, 'message' => 'c'],
        ]);

        $deleted = $this->safeDelete->deleteMessages(1, [10, 20]);

        self::assertSame(2, $deleted);
        $this->assertDatabaseMissing('tf_messages', ['account_id' => 1, 'id' => 10]);
        $this->assertDatabaseMissing('tf_messages', ['account_id' => 1, 'id' => 20]);
        $this->assertDatabaseHas('tf_messages', ['account_id' => 1, 'id' => 30]);
    }

    public function test_delete_messages_fires_event(): void
    {
        DB::table('tf_messages')->insert([
            ['account_id' => 1, 'id' => 5, 'constructor' => 'message', 'peer_type' => 3, 'peer_id' => 2001, 'date' => 1724852400, 'message' => 'x'],
        ]);

        $this->safeDelete->deleteMessages(1, [5]);

        Event::assertDispatched(MessagesDeleted::class, function (MessagesDeleted $event): bool {
            return $event->accountId === 1
                && $event->messageIds === [5]
                && $event->peerId === null;
        });
    }

    public function test_delete_nonexistent_messages_is_noop(): void
    {
        $deleted = $this->safeDelete->deleteMessages(1, [99999]);
        self::assertSame(0, $deleted);
        Event::assertNotDispatched(MessagesDeleted::class);
    }
}
