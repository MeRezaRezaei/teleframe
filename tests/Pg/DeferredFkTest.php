<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUser;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;

/**
 * Curated-dial FK truth (replaces the Night W3 deferrable-FK and the legacy
 * zero-FK proofs, re-based to the Task-8 contract).
 *
 * The curated dial carries CROSS-DOMAIN FKs only where the NF5 plan promises
 * them (2026_09_14_299999, asserted by tests/Mirror/RelationIntegrityTest):
 *  - tf_messages_media   (account_id, id) → tf_messages            CASCADE
 *  - tf_messages_entities (account_id, id) → tf_messages           CASCADE
 *  - tf_channel_participants (account_id, channel_id) → tf_channels RESTRICT
 * The polymorphic peer triad (peer_type/peer_id on tf_messages / tf_dialogs)
 * and the media→documents/photos refs are deliberately NOT DB-constrained
 * (one FK cannot branch on peer_type) — enforced in app code instead, so
 * out-of-order peer writes still commit inside one transaction.
 *
 * Intra-dial FKs declared inside the domain migrations themselves (tf_updates
 * → telegram_accounts, the 1:1/1:N update children → tf_updates, participant
 * children → tf_channel_participants) are part of the same real dial.
 */
final class DeferredFkTest extends PgTestCase
{
    private const ACCOUNT = 7;

    private const USER_ID = 501558149;

    private const CHANNEL_ID = 1737473577;

    /**
     * The Task-8 promised cross-domain FKs (child table → parent + on-delete),
     * mirroring tests/Mirror/RelationIntegrityTest::CROSS_DOMAIN_FKS.
     *
     * @var array<string, array{parent:string, action:string}>
     */
    private const CROSS_DOMAIN_FKS = [
        'tf_messages_media' => ['parent' => 'tf_messages', 'action' => 'ON DELETE CASCADE'],
        'tf_messages_entities' => ['parent' => 'tf_messages', 'action' => 'ON DELETE CASCADE'],
        'tf_channel_participants' => ['parent' => 'tf_channels', 'action' => 'ON DELETE RESTRICT'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateDomainSet();
    }

    public function test_peer_ref_columns_hold_the_canonical_pair_not_uuids(): void
    {
        $colType = DB::selectOne(
            'SELECT format_type(a.atttypid, a.atttypmod) AS t FROM pg_attribute a '
            .'WHERE a.attrelid = ?::regclass AND a.attname = ?',
            ['tf_messages', 'peer_id'],
        );
        self::assertNotNull($colType);
        self::assertSame('bigint', $colType->t, 'tf_messages.peer_id is a signed peer long');

        DB::table('tf_messages')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 9001,
            'peer_type' => PeerShapeTool::PEER_USER,
            'peer_id' => self::USER_ID,
            'constructor' => 'message',
            'date' => 1724852400,
            'message' => 'canonical peer pair',
        ]);
        DB::table('tf_messages_from_id')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 9001,
            'from_id_type' => PeerShapeTool::PEER_USER,
            'from_id_id' => self::USER_ID,
        ]);

        $row = DB::table('tf_messages')
            ->where('account_id', self::ACCOUNT)
            ->where('id', 9001)
            ->first();
        self::assertNotNull($row);
        self::assertSame(PeerShapeTool::PEER_USER, (int) $row->peer_type);
        self::assertSame(self::USER_ID, (int) $row->peer_id, 'peer pair round-trips through the truth columns');
        self::assertSame('message', (string) $row->constructor);
        self::assertEquals(1, (int) DB::table('tf_messages_from_id')->where('from_id_id', self::USER_ID)->count());
    }

    /**
     * The Task-8 FK set from RelationIntegrityTest exists on real PG with the
     * exact promised columns and on-delete actions — the peer side (tf_messages)
     * stays FK-less by design (app-enforced, one FK cannot branch on peer_type).
     */
    public function test_task_8_fk_set_matches_relation_integrity_contract(): void
    {
        $byChild = [];
        foreach ($this->foreignKeys() as $row) {
            $byChild[$row->child][] = $row->def;
        }

        foreach (self::CROSS_DOMAIN_FKS as $child => $expected) {
            $defs = $byChild[$child] ?? [];
            self::assertNotEmpty($defs, "{$child} carries a Task-8 FK");
            $joined = implode(' ', $defs);
            self::assertStringContainsString("REFERENCES {$expected['parent']}", $joined, "{$child} FK references {$expected['parent']}");
            self::assertStringContainsString($expected['action'], $joined, "{$child} FK on-delete action is {$expected['action']}");
        }

        // The (account_id, id) key shape of the media/entity parents.
        self::assertStringContainsString(
            'REFERENCES tf_messages(account_id, id)',
            implode(' ', $byChild['tf_messages_media'] ?? []),
            'tf_messages_media FK constrains the shared (account_id, id) message key',
        );
        self::assertStringContainsString(
            'REFERENCES tf_messages(account_id, id)',
            implode(' ', $byChild['tf_messages_entities'] ?? []),
            'tf_messages_entities FK constrains the shared (account_id, id) message key',
        );

        // tf_messages itself holds zero FKs — the polymorphic peer pair is
        // app-enforced, exactly as the 299999 migration documents.
        self::assertSame([], $byChild['tf_messages'] ?? [], 'tf_messages carries no peer FK (app-enforced triad)');

        // The Task-8 FK set is exactly what it promises: the only cross-domain
        // FK-bearing tables are the three above (participant FK set is
        // dominated by its intra-dial account FK + the RESTRICT channel FK).
        $crossDomain = array_values(array_filter(array_keys($byChild), static fn (string $t): bool => str_starts_with($t, 'tf_')));
        self::assertContains('tf_messages_media', $crossDomain);
        self::assertContains('tf_messages_entities', $crossDomain);
        self::assertContains('tf_channel_participants', $crossDomain);
    }

    /**
     * Out-of-order ingest inside ONE transaction: the message row lands
     * BEFORE its referenced user/channel rows in the same transaction.
     * The peer triad is not DB-constrained — any order commits.
     */
    public function test_child_before_parent_in_one_transaction_commits(): void
    {
        DB::transaction(function (): void {
            // Message first: references a user + channel that do NOT exist yet.
            DB::table('tf_messages')->insert([
                'account_id' => self::ACCOUNT,
                'id' => 1001,
                'peer_type' => PeerShapeTool::PEER_CHANNEL,
                'peer_id' => self::CHANNEL_ID,
                'constructor' => 'message',
                'date' => 1724852400,
                'message' => 'out-of-order child',
            ]);

            // Parents afterwards, SAME transaction — nothing to defer:
            // peer correctness is enforced in app code, not by the DB.
            (new TfUser(['account_id' => self::ACCOUNT, 'id' => self::USER_ID, 'constructor' => 'user']))->save();
            (new TfChannel(['account_id' => self::ACCOUNT, 'id' => self::CHANNEL_ID, 'constructor' => 'channel', 'title' => 'Teleframe Café']))->save();
        });

        self::assertSame(1, TfMessage::forAccount(self::ACCOUNT)->where('id', 1001)->count(), 'message row committed');
        self::assertSame(1, TfUser::forAccount(self::ACCOUNT)->where('id', self::USER_ID)->count());
        self::assertSame(1, TfChannel::forAccount(self::ACCOUNT)->where('id', self::CHANNEL_ID)->count());
    }

    /**
     * Update path: tf_updates accepts a row keyed (account_id, seq, position)
     * carrying the `constructor` discriminator + extracted fact children, with
     * its representative parent telegram_accounts row in place (the intra-dial
     * account FK holds on real PG).
     */
    public function test_update_path_writes_tf_updates_row(): void
    {
        DB::table('telegram_accounts')->insert([
            'id' => self::ACCOUNT,
            'label' => 'deferred-fk-'.self::ACCOUNT,
            'type' => 'user',
            'dc_id' => 2,
        ]);

        $update = new TfUpdate([
            'account_id' => self::ACCOUNT,
            'seq' => 0,
            'position' => 0,
            'constructor' => 'updateNewMessage',
        ]);
        $update->save();
        $update->pts()->create([...$update->childKey(), 'pts' => 1349]);
        $update->ptsCount()->create([...$update->childKey(), 'pts_count' => 1]);
        $update->message()->create([...$update->childKey(),
            'peer_type' => PeerShapeTool::PEER_CHANNEL,
            'peer_id' => self::CHANNEL_ID,
            'message_id' => 1186,
        ]);

        $row = TfUpdate::forAccount(self::ACCOUNT)->sole();
        self::assertSame('updateNewMessage', $row->constructor);
        self::assertSame(1349, (int) $row->pts()->sole()->pts);
        self::assertSame(1186, (int) $row->message()->sole()->message_id);
    }

    /**
     * @return array<int, object{child:string, def:string}>
     */
    private function foreignKeys(): array
    {
        return DB::select(
            'SELECT c.conrelid::regclass::text AS child, pg_get_constraintdef(c.oid) AS def '
            .'FROM pg_constraint c '
            .'JOIN pg_namespace n ON n.oid = c.connamespace '
            ."WHERE n.nspname = ? AND c.contype = 'f' AND c.conrelid::regclass::text LIKE 'tf\\_%' "
            .'ORDER BY child, def',
            [self::$pgSchema],
        );
    }
}
