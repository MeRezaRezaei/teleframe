<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;

/**
 * Phase C re-baseline of the plan Task-1 ingest test onto the curated dial:
 * flat-constructor ingest of a message wire payload into the hand-authored
 * tf_messages table — one row per (telegram message id, tenant), NO tl_data
 * JSONB / constructor_id (legacy extracted surface is gone). Unknown ctors
 * store nothing and never throw; only a missing '_' constructor is loud.
 */
final class UpdateIngestorTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const OTHER_ACCOUNT = 8;

    private const MESSAGE_ID = 1186;

    private const CHANNEL_ID = 1737473577;

    /**
     * Canned wire payload for message#7600b9d3: snake keys, canonical peer
     * ctor objects (peerChannel), wire-false bools absent. Only the hot-path
     * columns the curated tf_messages dial owns are persisted.
     *
     * @return array<string, mixed>
     */
    private static function messagePayload(string $text = 'Check https://t.me/teleframe from @Reza'): array
    {
        return [
            '_' => 'message',
            'out' => true,
            'id' => self::MESSAGE_ID,
            'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::CHANNEL_ID],
            'date' => 1724852400,
            'message' => $text,
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

    public function test_ingests_flat_message_into_domain_row(): void
    {
        $ingestor = new UpdateIngestor;
        $user = $ingestor->ingest(self::messagePayload(), self::ACCOUNT);

        self::assertInstanceOf(TfMessage::class, $user);
        self::assertSame(self::MESSAGE_ID, (int) $user->getAttribute('id'), 'native Telegram id is half the PK');
        self::assertSame(self::ACCOUNT, (int) $user->getAttribute('account_id'));

        $row = DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', self::MESSAGE_ID)->first();
        self::assertNotNull($row);
        self::assertSame('message', $row->constructor, 'constructor as the ctor name, no crc32 column');
        self::assertSame(3, (int) $row->peer_type, 'peerChannel normalizes to peer_type 3');
        self::assertSame(self::CHANNEL_ID, (int) $row->peer_id);
        self::assertSame(1724852400, (int) $row->date);
        self::assertSame('Check https://t.me/teleframe from @Reza', $row->message);

        // The curated surface has no tl_data / extracted is_out: booleans
        // are live wire-false columns and the inline peer halves carry the
        // canonical pair.
        self::assertTrue((bool) $row->out);
        self::assertSame([], array_intersect(['tl_data', 'constructor_id'], array_keys((array) $row)));
    }

    public function test_re_ingest_is_idempotent(): void
    {
        $ingestor = new UpdateIngestor;
        $first = $ingestor->ingest(self::messagePayload(), self::ACCOUNT);

        $second = $ingestor->ingest(self::messagePayload(), self::ACCOUNT);

        self::assertSame((int) $first->getAttribute('id'), (int) $second->getAttribute('id'), 'same native id reused');
        self::assertSame(self::ACCOUNT, (int) $second->getAttribute('account_id'));
        self::assertSame(1, TfMessage::forAccount(self::ACCOUNT)->count(), 'upsert-stable on (account_id, id)');
        self::assertSame(1, DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count());
    }

    public function test_message_rows_are_tenant_scoped(): void
    {
        // Composite key (account_id, id): each tenant holds its own row for
        // the same Telegram message.
        $ingestor = new UpdateIngestor;
        $a = $ingestor->ingest(self::messagePayload(), self::ACCOUNT);
        $b = $ingestor->ingest(self::messagePayload('edited under tenant 8'), self::OTHER_ACCOUNT);

        self::assertSame(2, TfMessage::acrossAccounts()->count(), 'one row per tenant');
        self::assertSame(self::MESSAGE_ID, (int) $a->getAttribute('id'));
        self::assertSame(self::ACCOUNT, (int) $a->getAttribute('account_id'));
        self::assertSame(self::MESSAGE_ID, (int) $b->getAttribute('id'), 'same native id ...');
        self::assertSame(self::OTHER_ACCOUNT, (int) $b->getAttribute('account_id'), '... but a distinct tenant row');

        // Per-tenant mutation isolation: the tenant 8 edit leaves 7 alone.
        self::assertSame(
            'Check https://t.me/teleframe from @Reza',
            TfMessage::forAccount(self::ACCOUNT)->sole()->getAttribute('message'),
        );
        self::assertSame(
            'edited under tenant 8',
            TfMessage::forAccount(self::OTHER_ACCOUNT)->sole()->getAttribute('message'),
        );
    }

    public function test_ephemeral_constructor_has_no_persistable_node(): void
    {
        // userStatusOnline is a real TL constructor but an ephemeral type
        // with no curated mirror surface: ingest walks it, persists nothing,
        // and returns null — never a throw (the unknown-ctor rule).
        $root = (new UpdateIngestor)->ingest(['_' => 'userStatusOnline', 'expires' => 0], self::ACCOUNT);

        self::assertNull($root);
        self::assertSame(0, DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count());
    }

    public function test_unknown_constructor_stores_nothing(): void
    {
        $root = (new UpdateIngestor)->ingest(['_' => 'noSuchConstructorAtAll'], self::ACCOUNT);

        self::assertNull($root, 'unknown ctor is a clue, never a throw');
        self::assertSame(0, DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count());
    }

    public function test_missing_constructor_is_loud(): void
    {
        // The one loud error in curated reality: a payload with no '_'.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'_'");
        (new UpdateIngestor)->ingest(['pts' => 1], self::ACCOUNT);
    }
}
