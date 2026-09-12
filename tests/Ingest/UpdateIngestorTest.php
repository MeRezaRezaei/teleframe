<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;

/**
 * Plan Task 1: flat-constructor ingest of user#31774388 (v227) into the
 * TDLib-style domain table tf_users — one row per (telegram id, tenant)
 * with extracted query columns + full tl_data JSONB, idempotent re-ingest.
 */
final class UpdateIngestorTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const USER_ID = 501558149;

    /**
     * Canned v227 wire payload for user#31774388: snake keys, raw flag ints
     * (teleframe arrays-in truth). Flat fields only — nested refs (photo,
     * status, emoji_status, colors) and vectors (restriction_reason,
     * usernames) ride along inside tl_data JSONB; only hot-path columns
     * are extracted.
     */
    private static function userPayload(string $firstName = 'Reza'): array
    {
        return [
            '_' => 'user',
            // access_hash | first_name | last_name | username | phone | lang_code | premium
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3) | (1 << 4) | (1 << 22) | (1 << 28),
            'id' => self::USER_ID,
            'access_hash' => -5988024083302710253,
            'first_name' => $firstName,
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
            'phone' => '989121234567',
            'lang_code' => 'en',
            'flags2' => (1 << 4), // stories_unavailable
            'stories_unavailable' => true,
            'premium' => true, // flags.28?true — set-flag bools arrive as keys
        ];
    }

    public function test_boot_is_removed_and_migration_surface_stays_queryable(): void
    {
        // Phase 0 Task 3 (spec D3): ingest NEVER runs migrations as a side
        // effect. The explicit surface is migrationPaths(); hosts migrate.
        self::assertFalse(method_exists(UpdateIngestor::class, 'boot'));

        $paths = UpdateIngestor::migrationPaths();
        self::assertNotEmpty($paths);

        // migrationPaths() mixes the curated dial directory (first element)
        // with off-dial entity migration FILES: assert dir vs file correctly.
        $dialDir = $paths[0];
        self::assertDirectoryExists($dialDir);
        foreach (array_slice($paths, 1) as $file) {
            self::assertFileExists($file);
        }
    }

    public function test_ingests_flat_user_into_domain_row(): void
    {
        $user = (new UpdateIngestor())->ingest(self::userPayload(), self::ACCOUNT);

        self::assertInstanceOf(TlUser::class, $user);
        self::assertSame(self::USER_ID, (int) $user->id, 'native Telegram id is the PK');
        self::assertSame(self::ACCOUNT, (int) $user->account_id);
        self::assertSame(0x31774388, (int) $user->constructor_id);

        $row = TlUser::acrossAccounts()->sole();
        self::assertSame(self::USER_ID, (int) $row->id);
        self::assertSame(-5988024083302710253, (int) $row->access_hash);
        self::assertSame('Reza', $row->first_name);
        self::assertSame('Rezaei', $row->last_name);
        self::assertSame('RezaRezaei', $row->username);
        self::assertSame('989121234567', $row->phone);
        self::assertTrue((bool) $row->is_premium, 'bare TL flag key premium → is_premium column');
        self::assertFalse((bool) $row->is_bot);
        self::assertFalse((bool) $row->is_deleted);

        // The domain row carries everything: extracted columns + full tl_data.
        self::assertSame('user', $row->tl_data['_']);
        self::assertSame('en', $row->tl_data['lang_code'], 'unextracted fields survive in JSONB');
    }

    public function test_re_ingest_is_idempotent(): void
    {
        $ingestor = new UpdateIngestor();
        $first = $ingestor->ingest(self::userPayload(), self::ACCOUNT);

        $second = $ingestor->ingest(self::userPayload(), self::ACCOUNT);

        self::assertSame((int) $first->id, (int) $second->id, 'same native id reused');
        self::assertSame(self::ACCOUNT, (int) $second->account_id);
        self::assertSame(1, TlUser::acrossAccounts()->count());
        self::assertSame($first->username, $second->username);
        self::assertSame('user', $second->tl_data['_'], 'latest constructor wins');
    }

    public function test_user_rows_are_tenant_scoped(): void
    {
        // Composite PK (id, account_id): each tenant holds its own row for
        // the same Telegram entity.
        $ingestor = new UpdateIngestor();
        $a = $ingestor->ingest(self::userPayload(), self::ACCOUNT);
        $b = $ingestor->ingest(self::userPayload('Ali'), 8);

        self::assertSame(2, TlUser::acrossAccounts()->count(), 'one row per tenant');
        self::assertSame(self::USER_ID, (int) $a->id);
        self::assertSame(self::ACCOUNT, (int) $a->account_id);
        self::assertSame(self::USER_ID, (int) $b->id, 'same native id ...');
        self::assertSame(8, (int) $b->account_id, '... but a distinct tenant row');

        // Per-tenant mutation isolation: renaming under account 8 leaves 7 alone.
        self::assertSame('Reza', TlUser::forAccount(self::ACCOUNT)->sole()->first_name);
        self::assertSame('Ali', TlUser::forAccount(8)->sole()->first_name);
    }

    public function test_ephemeral_constructor_has_no_persistable_node(): void
    {
        // userStatusOnline is a real TL constructor but an ephemeral type
        // (no domain table): ingest walks it, persists nothing, and fails
        // loudly naming the situation — not the constructor.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no persistable domain node');
        (new UpdateIngestor())->ingest(['_' => 'userStatusOnline', 'expires' => 0], self::ACCOUNT);
    }

    public function test_unknown_constructor_fails_loudly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('unknown TL constructor');
        (new UpdateIngestor())->ingest(['_' => 'noSuchConstructorAtAll'], self::ACCOUNT);
    }
}
