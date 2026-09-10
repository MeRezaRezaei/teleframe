<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\IdentityLock;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;

/**
 * Plan Task 1: flat-constructor ingest of user#31774388 (v227) —
 * tenant-scoped anchor + verbatim instance row + idempotent re-ingest.
 */
final class UpdateIngestorTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    /**
     * Canned v227 wire payload for user#31774388: snake keys, raw flag ints
     * (teleframe arrays-in truth). Flat fields only — nested refs (photo,
     * status, emoji_status, colors) and vectors (restriction_reason,
     * usernames) are Task 3's walker scope.
     */
    private static function userPayload(): array
    {
        return [
            '_' => 'user',
            // access_hash | first_name | last_name | username | phone | lang_code | premium
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3) | (1 << 4) | (1 << 22) | (1 << 28),
            'id' => 501558149,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
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

    public function test_ingests_flat_user_into_anchor_and_instance(): void
    {
        $instance = (new UpdateIngestor())->ingest(self::userPayload(), self::ACCOUNT);

        self::assertInstanceOf(TlUserUser::class, $instance);

        $anchor = TlUser::query()->sole();
        self::assertIsInt($anchor->id);
        self::assertGreaterThan(0, $anchor->id, 'anchor PK must be a positive integer id');
        self::assertSame(0x31774388, $anchor->constructor_id);
        self::assertSame('user', $anchor->constructor_name);
        self::assertSame(self::ACCOUNT, (int) $anchor->account_id);
        self::assertSame($anchor->id, $instance->id, 'instance shares the anchor PK (spec §4.2)');

        $row = TlUserUser::query()->sole();
        self::assertSame(501558149, $row->tl_id);
        self::assertSame(
            (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3) | (1 << 4) | (1 << 22) | (1 << 28),
            $row->flags,
            'flags stored as the raw carrier int',
        );
        self::assertSame((1 << 4), $row->flags2);
        self::assertSame(-5988024083302710253, $row->access_hash);
        self::assertSame('Reza', $row->first_name);
        self::assertSame('Rezaei', $row->last_name);
        self::assertSame('RezaRezaei', $row->username);
        self::assertSame('989121234567', $row->phone);
        self::assertSame('en', $row->lang_code);
        self::assertTrue($row->premium);
        self::assertTrue($row->stories_unavailable);
        self::assertFalse($row->bot);
        self::assertNull($row->photo);
    }

    public function test_re_ingest_is_idempotent(): void
    {
        $ingestor = new UpdateIngestor();
        $first = $ingestor->ingest(self::userPayload(), self::ACCOUNT);
        $anchorId = (int) $first->id;

        $second = $ingestor->ingest(self::userPayload(), self::ACCOUNT);

        self::assertSame($anchorId, (int) $second->id, 'same anchor id reused');
        self::assertSame(1, TlUser::query()->count());
        self::assertSame(1, TlUserUser::query()->count());
        self::assertSame($first->tl_id, $second->tl_id);
        self::assertSame($first->username, $second->username);
    }

    public function test_global_id_anchor_is_shared_across_tenants(): void
    {
        // Global-ID types (id:long → Telegram ID IS the PK) produce ONE
        // row per Telegram entity — multiple accounts share the same anchor.
        $ingestor = new UpdateIngestor();
        $a = $ingestor->ingest(self::userPayload(), self::ACCOUNT);
        $b = $ingestor->ingest(self::userPayload(), 8);

        self::assertSame(1, TlUser::query()->count(), 'one shared anchor for global-ID type');
        self::assertSame(1, TlUserUser::query()->count(), 'one shared instance');
        self::assertSame((int) $a->id, (int) $b->id, 'both accounts get the same anchor PK');
    }

    public function test_identity_resolution_serializes_and_releases_its_lock(): void
    {
        // P2 M3: the identity path runs under IdentityLock — after ingest
        // (even nested same-identity nodes) the in-process key must be
        // fully released, never leaked across payloads/accounts.
        $ingestor = new UpdateIngestor();
        $ingestor->ingest(self::userPayload(), self::ACCOUNT);
        $ingestor->ingest(self::userPayload(), self::ACCOUNT);

        // Merged schema: identity column is 'tl_id' (Naming::RESERVED maps 'id' → 'tl_id').
        self::assertSame(0, IdentityLock::depth('tl_anchor:' . self::ACCOUNT . ':tl_id:501558149'));
        self::assertSame(1, TlUser::query()->count(), 'guard: idempotent ingest still holds');
    }

    public function test_unknown_constructor_fails_loudly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('userStatusOnline');
        (new UpdateIngestor())->ingest(['_' => 'userStatusOnline', 'expires' => 0], self::ACCOUNT);
    }
}
