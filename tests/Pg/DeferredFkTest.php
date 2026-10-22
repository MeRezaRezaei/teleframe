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
        // Seeded for EVERY test, not just the one that writes it inline: the tenant FK
        // reads telegram_accounts.USER_ID (see PgTestCase::seedPgTenants).
        $this->seedPgTenants(self::ACCOUNT);
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
        // FIXTURE CORRECTION (2026-10-03, Task 2.2): the from_id row is a
        // CHILD of the message, so it carries the SAME peer-scoped key the
        // parent declares — (account_id, peer_type, peer_id, id) — because
        // Telegram numbers messages PER CHAT and `id` alone names no message.
        // On Postgres this is the test's sharper half: SQLite lets a NOT NULL
        // column be left out only in the sense that it raises too, but PG is the
        // track that actually proves the constraint is real ("null value in
        // column peer_type ... violates not-null constraint"). The values are
        // the parent's own; assertions below are unchanged.
        DB::table('tf_messages_from_id')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => PeerShapeTool::PEER_USER,
            'peer_id' => self::USER_ID,
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

        // The PEER-SCOPED key shape of the media/entity parents.
        //
        // RAISED, 2026-10-03 (Task 2.2). This asserted the REFERENCED column
        // list was (account_id, id), which was the defect: Telegram numbers
        // messages PER CHAT, so (account_id, id) is not unique on `tf_messages`
        // across a real account's dialogs, and a constraint built on it cannot
        // express what it claims to express. The referenced list is now the full
        // (account_id, peer_type, peer_id, id).
        //
        // `tf_messages_media` names the LOCAL legs with a `message_` prefix —
        // that table already owns peer_type/peer_id for a peer inside its own
        // payload — while the REFERENCED list stays the parent's unprefixed
        // pair, because a foreign key matches on the referenced names. Both
        // halves are asserted, so the disambiguation cannot silently collapse
        // back into one set of names.
        self::assertStringContainsString(
            'REFERENCES tf_messages(account_id, peer_type, peer_id, id)',
            implode(' ', $byChild['tf_messages_media'] ?? []),
            'tf_messages_media FK must reference the FULL peer-scoped message key',
        );
        self::assertStringContainsString(
            'FOREIGN KEY (account_id, message_peer_type, message_peer_id, id)',
            implode(' ', $byChild['tf_messages_media'] ?? []),
            'tf_messages_media FK must constrain the PREFIXED local legs — the table already owns peer_type/peer_id for a peer inside its own payload',
        );
        self::assertStringContainsString(
            'REFERENCES tf_messages(account_id, peer_type, peer_id, id)',
            implode(' ', $byChild['tf_messages_entities'] ?? []),
            'tf_messages_entities FK must reference the FULL peer-scoped message key',
        );
        self::assertStringContainsString(
            'FOREIGN KEY (account_id, peer_type, peer_id, id)',
            implode(' ', $byChild['tf_messages_entities'] ?? []),
            'tf_messages_entities FK must constrain the peer-scoped local legs',
        );

        // tf_messages holds no PEER FK — the polymorphic peer pair is app-enforced,
        // exactly as the 299999 migration documents.
        //
        // 🔴 The assertion used to be `assertSame([], $byChild['tf_messages'])`, i.e.
        // "zero FKs of any kind", and it only held by ACCIDENT: it was written before
        // the tenant FK existed in a shape the query could see. Postgres then reported
        //   FOREIGN KEY (account_id) REFERENCES telegram_accounts(user_id) ON DELETE CASCADE
        // which is NOT a peer FK — it is the tenant link, and it is CORRECT and required.
        //
        // So the filter now says what the assertion's own message always claimed:
        // no peer FK. A tenant FK to telegram_accounts is expected and is asserted
        // present below, so this is narrowing the CLAIM to its real subject, not
        // weakening it — the peer half is still exactly-empty.
        $messagePeerFks = array_values(array_filter(
            $byChild['tf_messages'] ?? [],
            static fn (string $fk): bool => ! str_contains($fk, 'telegram_accounts'),
        ));
        self::assertSame(
            [],
            $messagePeerFks,
            'tf_messages carries no PEER FK (app-enforced triad) — a tenant FK to '
            .'telegram_accounts is expected and is not a peer reference',
        );
        self::assertStringContainsString(
            'REFERENCES telegram_accounts(user_id)',
            implode(' ', $byChild['tf_messages'] ?? []),
            'tf_messages DOES carry its tenant FK, and it targets telegram_accounts.user_id '
            .'(2026_10_04_000200 retargeted it off .id per D-022)',
        );

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
        // The parent telegram_accounts row is planted by setUp() via
        // seedPgTenants(), which writes BOTH id and user_id. Seeding it inline here
        // duplicated the primary key once setUp took ownership (SQLSTATE[23505]).

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
