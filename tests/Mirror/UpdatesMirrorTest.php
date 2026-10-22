<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannelParticipant;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannelUpdate;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdateDifference;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdateRouting;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdateState;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * NF5 updates-domain mirror test (plan Task 5): hand-authored tf_update_* /
 * tf_updates_* / tf_channel_* tables are migrated via UpdateIngestor's seam,
 * stay NF5-clean (zero nullable, no json/blob), and round-trip the named
 * wire facts with the hand-authored models + account scoping.
 */
final class UpdatesMirrorTest extends IngestTestCase
{
    /**
     * The tenants this fixture writes under.
     *
     * MEASURED, not assumed: extracted from the account ids this file
     * actually inserts. It was undeclared, and with foreign-key enforcement
     * on (19 constraints) every row was refused at the DATABASE rather than
     * at the code under test — the test failed on its own fixture.
     *
     * Declared HERE rather than by relaxing seedIngestTenant(): that helper
     * contracts that each test declares the tenants it means to use, so a
     * test writing under an unplanted id fails LOUDLY. These were failing
     * loudly, which is the mechanism working. A blanket auto-seed would also
     * make the orphan-refusal tests unable to plant an orphan.
     *
     * ## 8 IS WRITTEN UNDER BY A LITERAL IN THE BODY, NOT BY A CONSTANT
     *
     * `test_rows_are_tenant_scoped_by_composite_key` loops
     * `foreach ([self::ACCOUNT, 8] as $account)` — the second tenant is a bare
     * literal, so anything that reads the file's `const` declarations sees only
     * `self::ACCOUNT` and misses it. MEASURED: with `[7]` alone that test died
     * on `insert into "tf_updates" ("account_id", …) values (8, 1, 0, …)` —
     * `19 FOREIGN KEY constraint failed`.
     *
     * Note the loop's own `updateOrInsert(['id' => $account], …)` does NOT plant
     * the tenant: the tenant key is `telegram_accounts.USER_ID` (D-005), and that
     * upsert names `id`. Only the `ingestTenantIds()` declaration here does, and
     * that indirection is exactly why the id has to be declared.
     */
    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT, 8];
    }

    private const ACCOUNT = 7;

    private const UPDATE_TABLES = [
        'tf_updates',
        'tf_updates_pts',
        'tf_updates_pts_count',
        'tf_updates_qts',
        'tf_updates_date',
        'tf_updates_message',
        'tf_updates_messages',
        // `tf_updates_{channel,chat,user}` were retired 2026-10-10: the TL declares
        // `channel_id` / `chat_id` / `user_id` as a bare `long` on `Update`, so the
        // wire nests nothing there. Their values are COLUMNS on `tf_updates` now, and
        // that table is still in this list — so the NF5-clean assertion above covers
        // the folded columns, including that they are NOT NULL (declared
        // `NOT NULL DEFAULT 0`, the same convention `date` already uses here).
        // `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact`.
        //
        // `tf_updates_from` and `tf_updates_peer` STAY: `from_id`/`peer` are `Peer`
        // on every `Update` constructor that declares them, so those two tables are
        // correct nested storage translations, not fabrications.
        'tf_updates_peer',
        'tf_updates_from',
        'tf_updates_max_id',
        'tf_updates_still_unread_count',
        'tf_updates_top_msg_id',
        'tf_updates_folder_id',
        'tf_updates_status',
        'tf_updates_action',
        'tf_update_routing',
        'tf_update_routing_date',
        'tf_update_routing_seq_start',
        'tf_update_routing_ack',
        'tf_update_state',
        'tf_channel_participants',
        'tf_channel_participants_peer',
        // `tf_channel_participants_{inviter,kicked_by,promoted_by}` were retired
        // 2026-10-08: the TL declares `inviter_id` / `promoted_by` / `kicked_by`
        // as bare `long` on one constructor each, so the wire nests nothing there.
        // Their values are COLUMNS on `tf_channel_participants` now, and that table
        // is still in this list — so the NF5-clean assertion above now covers the
        // folded columns, including that they are NOT NULL (declared
        // `NOT NULL DEFAULT 0`, the same convention `date` already uses here).
        'tf_channel_participants_subscription_until_date',
        'tf_channel_participants_rank',
        'tf_channel_updates',
        'tf_channel_updates_pts',
        'tf_channel_updates_timeout',
        'tf_channel_updates_dialog',
        'tf_update_differences',
        'tf_update_differences_date',
        'tf_update_differences_seq',
        'tf_update_differences_pts',
        'tf_update_differences_state',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('telegram_accounts')->insert([
            'id' => self::ACCOUNT,
            'label' => 'updates-mirror-'.self::ACCOUNT,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    public function test_seam_points_at_the_hand_authored_dial_files(): void
    {
        $paths = UpdateIngestor::entityMigrationPaths();
        self::assertCount(2, $paths);
        foreach ($paths as $path) {
            self::assertFileExists($path);
        }

        $names = array_map('basename', $paths);
        self::assertSame([
            '2026_09_14_200030_create_tf_updates_tables.php',
            '2026_09_14_200031_create_tf_channel_participants_tables.php',
        ], $names);
    }

    public function test_updates_mirror_schema_is_nf5_clean(): void
    {
        foreach (self::UPDATE_TABLES as $tableName) {
            self::assertTrue(Schema::hasTable($tableName), "{$tableName} missing");
            foreach (Schema::getColumns($tableName) as $col) {
                $type = strtolower((string) $col['type']);
                self::assertStringNotContainsString('json', $type, "{$tableName}.{$col['name']} must not be json");
                self::assertStringNotContainsString('blob', $type, "{$tableName}.{$col['name']} must not be blob");
                self::assertFalse((bool) $col['nullable'], "{$tableName}.{$col['name']} must be NOT NULL");
            }
        }
    }

    public function test_update_new_message_fact_round_trips_with_children(): void
    {
        $update = new TfUpdate([
            'account_id' => self::ACCOUNT,
            'seq' => 5,
            'position' => 0,
            'constructor' => 'updateNewMessage',
        ]);
        $update->save();

        $update->pts()->create([...$update->childKey(), 'pts' => 1400]);
        $update->ptsCount()->create([...$update->childKey(), 'pts_count' => 1]);
        $update->message()->create([...$update->childKey(),
            'peer_type' => 1,
            'peer_id' => 501558149,
            'message_id' => 77,
        ]);

        self::assertSame(1, TfUpdate::forAccount(self::ACCOUNT)->count());

        $stored = TfUpdate::forAccount(self::ACCOUNT)->sole();
        self::assertSame(5, (int) $stored->getAttribute('seq'));
        self::assertSame('updateNewMessage', $stored->getAttribute('constructor'));

        self::assertSame(1400, (int) $stored->pts()->sole()->getAttribute('pts'));
        self::assertSame(1, (int) $stored->ptsCount()->sole()->getAttribute('pts_count'));
        $linked = $stored->message()->sole();
        self::assertSame(501558149, (int) $linked->getAttribute('peer_id'));
        self::assertSame(77, (int) $linked->getAttribute('message_id'));
    }

    public function test_delete_messages_vector_is_one_to_many_by_slot(): void
    {
        $update = new TfUpdate([
            'account_id' => self::ACCOUNT,
            'seq' => 6,
            'position' => 3,
            'constructor' => 'updateDeleteMessages',
        ]);
        $update->save();

        $update->messages()->create([...$update->childKey(), 'message_position' => 0, 'message_id' => 101]);
        $update->messages()->create([...$update->childKey(), 'message_position' => 1, 'message_id' => 99]);

        $ids = $update->messages()
            ->orderBy('message_position')
            ->pluck('message_id')
            ->all();
        self::assertSame([101, 99], $ids);
    }

    public function test_update_user_status_and_typing_facts_round_trip(): void
    {
        // `tf_updates_chat` is RETIRED (2026_10_10_000100). This test used to
        // write a row into it and read the value back off the child. The TL
        // declares `chat_id:long` as a bare scalar on `updateChatUserTyping`,
        // so the wire nests no object there and a child TABLE asserted one —
        // the value is a COLUMN on the `tf_updates` fact row now.
        //
        // The fixture is therefore re-pointed, NOT weakened: the REAL value 22
        // is written to the parent column and asserted on the parent row, and
        // the sibling columns this constructor does NOT declare are asserted
        // at the wire-false sentinel 0 — so a fixture that wrote 0 everywhere
        // (which would satisfy a bare count) cannot pass this.
        $typing = new TfUpdate([
            'account_id' => self::ACCOUNT,
            'seq' => 7,
            'position' => 0,
            'constructor' => 'updateChatUserTyping',
            'chat_id' => 22,
        ]);
        $typing->save();
        $typing->from()->create([...$typing->childKey(), 'peer_type' => 1, 'peer_id' => 501558149]);
        $typing->action()->create([...$typing->childKey(), 'constructor' => 'sendMessageUploadPhotoAction', 'progress' => 50]);

        // `updateUserStatus#e5bdf8de user_id:long status:UserStatus` — the same
        // fold, the other scalar.
        $status = new TfUpdate([
            'account_id' => self::ACCOUNT,
            'seq' => 8,
            'position' => 0,
            'constructor' => 'updateUserStatus',
            'user_id' => 501558149,
        ]);
        $status->save();
        $status->status()->create([...$status->childKey(), 'constructor' => 'userStatusOnline', 'expires' => 1750000000]);

        // `tf_updates_from` deliberately STAYS: `from_id` is `Peer` on every
        // `Update` constructor that declares it, so that one IS a nested object.
        self::assertSame(501558149, (int) $typing->from()->sole()->getAttribute('peer_id'), 'the typed peer stays a NESTED object (from_id is a Peer), so its id lands on the tf_updates_from child, not folded onto the row');

        // The folded scalars, on the fact row, carrying the real values.
        self::assertSame(22, (int) $typing->getAttribute('chat_id'), 'a userTyping update carries chat_id and it must fold onto the row WITH its real value 22');
        self::assertSame(501558149, (int) $status->getAttribute('user_id'), 'a userStatus update carries user_id and it must fold onto the row WITH its real value');

        // And the strict half: the sibling folded columns these two
        // constructors do NOT declare read the wire-false sentinel, which is
        // what `NOT NULL DEFAULT 0` is for. A row written as 0 throughout
        // fails the two assertions above; a row written as 22 throughout fails
        // these. Neither can satisfy the set.
        self::assertSame(0, (int) $typing->getAttribute('user_id'), 'userTyping does NOT declare user_id, so that column must read the wire-false sentinel 0');
        self::assertSame(0, (int) $typing->getAttribute('channel_id'), 'userTyping does NOT declare channel_id either, so that column must read 0');
        self::assertSame(0, (int) $status->getAttribute('chat_id'), 'userStatus does NOT declare chat_id, so that column must read 0 — no row can satisfy both updates');
        self::assertSame(0, (int) $status->getAttribute('channel_id'), 'userStatus does NOT declare channel_id either, so that column must read 0');

        self::assertSame(50, (int) $typing->action()->sole()->getAttribute('progress'), 'the MessageAction progress must land on the folded tf_updates_actions child with its real value');
        self::assertSame('userStatusOnline', $status->status()->sole()->getAttribute('constructor'), 'the UserStatus must land on the folded tf_updates_status child carrying the real ctor');
        self::assertSame(1750000000, (int) $status->status()->sole()->getAttribute('expires'), 'the UserStatus expires must land on the folded child with its real value');

        // The retired tables are gone and must not be resurrected by any future
        // edit to this fixture — the reason both of the above are columns.
        self::assertFalse(Schema::hasTable('tf_updates_chat'), 'tf_updates_chat was retired 2026-10-10');
        self::assertFalse(Schema::hasTable('tf_updates_channel'), 'tf_updates_channel was retired 2026-10-10');
        self::assertFalse(Schema::hasTable('tf_updates_user'), 'tf_updates_user was retired 2026-10-10');
    }

    public function test_state_seam_upserts_snapshot_and_round_trips(): void
    {
        TfUpdateState::syncSnapshot(self::ACCOUNT, ['pts' => 10, 'qts' => 2, 'date' => 100, 'seq' => 1, 'unread_count' => 3]);
        TfUpdateState::syncSnapshot(self::ACCOUNT, ['pts' => 12, 'seq' => 2]);

        self::assertSame(1, TfUpdateState::forAccount(self::ACCOUNT)->count());
        $state = TfUpdateState::forAccount(self::ACCOUNT)->sole();
        self::assertSame(12, (int) $state->getAttribute('pts'));
        self::assertSame(2, (int) $state->getAttribute('qts'), 'absent fields fall back to the wire-false sentinel');
        self::assertSame(2, (int) $state->getAttribute('seq'));
    }

    public function test_routing_receipt_and_quick_ack_disposition(): void
    {
        $receipt = new TfUpdateRouting([
            'account_id' => self::ACCOUNT,
            'seq' => 90,
            'position' => 0,
            'constructor' => 'updatesCombined',
            'msg_id' => 912374801,
        ]);
        $receipt->save();
        $receipt->date()->create([...$receipt->childKey(), 'date' => 1750000000]);
        $receipt->seqStart()->create([...$receipt->childKey(), 'seq_start' => 1]);
        $receipt->ack()->create([...$receipt->childKey(), 'acked' => false, 'acked_at' => 0]);

        $stored = TfUpdateRouting::forAccount(self::ACCOUNT)->sole();
        self::assertSame(912374801, (int) $stored->getAttribute('msg_id'));
        self::assertSame(1750000000, (int) $stored->date()->sole()->getAttribute('date'));
        self::assertSame(1, (int) $stored->seqStart()->sole()->getAttribute('seq_start'));

        // Wire layer flips the ack disposition once msgs_ack is emitted.
        $stored->ack()->update(['acked' => true, 'acked_at' => 100]);
        self::assertTrue((bool) $stored->ack()->sole()->getAttribute('acked'));
        self::assertSame(100, (int) $stored->ack()->sole()->getAttribute('acked_at'));
    }

    public function test_channel_difference_and_participant_facts_round_trip(): void
    {
        // PARENT-FIRST FIXTURE (2026-10-06): `tf_channel_participants` carries a
        // DECLARED `(account_id, channel_id) -> tf_channels(account_id, id)`
        // foreign key, RESTRICT (2026_09_14_299999; MEASURED off the migrated
        // schema, not read off the source). The participant below is refused at
        // the DATABASE without its channel parent — a refusal with nothing to do
        // with the round-trip this test is about — so the parent is planted
        // first. Deliberately NOT `seedIngestPeer()`: nothing here needs an
        // access_hash row, and planting one would add a fact this test does not
        // assert and would have to then keep true.
        DB::table('tf_channels')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 999,
            'constructor' => 'channel',
            'title' => 'Participant Parent',
        ]);

        $diff = new TfChannelUpdate([
            'account_id' => self::ACCOUNT,
            'channel_id' => 999,
            'position' => 0,
            'constructor' => 'channelDifference',
            'is_final' => true,
        ]);
        $diff->save();
        $diff->pts()->create([...$diff->childKey(), 'pts' => 44]);
        $diff->timeout()->create([...$diff->childKey(), 'timeout' => 5]);

        self::assertSame(44, (int) $diff->pts()->sole()->getAttribute('pts'));
        self::assertSame(5, (int) $diff->timeout()->sole()->getAttribute('timeout'));

        // `channelParticipantAdmin#34c3bb53 … promoted_by:long …` is a bare
        // `long`, so `tf_channel_participants_promoted_by` was retired
        // 2026-10-08 and `promoted_by` is a COLUMN on the fact row. The
        // fixture writes the real value onto the parent — it does not drop the
        // guard, and `rank` / `subscription_until_date` (genuine nested or
        // deliberately-kept scalars) still round-trip through their children.
        $participant = new TfChannelParticipant([
            'account_id' => self::ACCOUNT,
            'channel_id' => 999,
            'user_id' => 501558149,
            'constructor' => 'channelParticipantAdmin',
            'date' => 1750000000,
            'can_edit' => true,
            'promoted_by' => 7,
        ]);
        $participant->save();
        $participant->rank()->create([...$participant->childKey(), 'rank' => 'owner']);
        $participant->subscriptionUntilDate()->create([...$participant->childKey(), 'subscription_until_date' => 1751000000]);

        $stored = TfChannelParticipant::forAccount(self::ACCOUNT)->sole();
        self::assertTrue((bool) $stored->getAttribute('can_edit'));
        self::assertSame('owner', $stored->rank()->sole()->getAttribute('rank'));
        self::assertSame(1751000000, (int) $stored->subscriptionUntilDate()->sole()->getAttribute('subscription_until_date'));

        // The folded scalar, on the fact row, carrying the REAL value 7.
        self::assertSame(7, (int) $stored->getAttribute('promoted_by'));

        // `channelParticipantAdmin` declares `inviter_id` as `flags.1?long`
        // (flag unset here) and does not declare `kicked_by` at all, so both
        // read the wire-false sentinel 0. Same strictness argument as the
        // update scalars: a row written as 0 throughout fails the `promoted_by`
        // assertion above; a row written as 7 throughout fails these.
        self::assertSame(0, (int) $stored->getAttribute('inviter_id'));
        self::assertSame(0, (int) $stored->getAttribute('kicked_by'));

        // The retired tables are gone and must not come back.
        self::assertFalse(Schema::hasTable('tf_channel_participants_promoted_by'), 'retired 2026-10-08');
        self::assertFalse(Schema::hasTable('tf_channel_participants_inviter'), 'retired 2026-10-08');
        self::assertFalse(Schema::hasTable('tf_channel_participants_kicked_by'), 'retired 2026-10-08');
    }

    public function test_top_level_difference_summaries_round_trip(): void
    {
        $difference = new TfUpdateDifference([
            'account_id' => self::ACCOUNT,
            'position' => 0,
            'constructor' => 'difference',
        ]);
        $difference->save();
        $difference->date()->create([...$difference->childKey(), 'date' => 1750000000]);
        $difference->seq()->create([...$difference->childKey(), 'seq' => 12]);
        $difference->state()->create([...$difference->childKey(),
            'pts' => 1400,
            'qts' => 3,
            'date' => 1750000000,
            'seq' => 12,
            'unread_count' => 8,
        ]);

        self::assertSame(1400, (int) $difference->state()->sole()->getAttribute('pts'));
        self::assertSame(8, (int) $difference->state()->sole()->getAttribute('unread_count'));
    }

    public function test_rows_are_tenant_scoped_by_composite_key(): void
    {
        foreach ([self::ACCOUNT, 8] as $account) {
            DB::table('telegram_accounts')->updateOrInsert(['id' => $account], [
                'id' => $account,
                'label' => 'updates-mirror-'.$account,
                'type' => 'user',
                'dc_id' => 2,
            ]);
            $mirror = new TfUpdate(['account_id' => $account, 'seq' => 1, 'position' => 0, 'constructor' => 'updateNewMessage']);
            $mirror->save();
            $mirror->pts()->create([...$mirror->childKey(), 'pts' => $account * 100]);
        }

        self::assertSame(2, TfUpdate::acrossAccounts()->count(), 'same (seq, position) under two tenants');
        self::assertSame(1, TfUpdate::forAccount(self::ACCOUNT)->count());
        self::assertSame(800, (int) TfUpdate::forAccount(8)->sole()->pts()->sole()->getAttribute('pts'));
    }
}
