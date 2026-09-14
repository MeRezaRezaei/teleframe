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
    private const ACCOUNT = 7;

    private const UPDATE_TABLES = [
        'tf_updates',
        'tf_updates_pts',
        'tf_updates_pts_count',
        'tf_updates_qts',
        'tf_updates_date',
        'tf_updates_message',
        'tf_updates_messages',
        'tf_updates_channel',
        'tf_updates_chat',
        'tf_updates_user',
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
        'tf_channel_participants_inviter',
        'tf_channel_participants_promoted_by',
        'tf_channel_participants_kicked_by',
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
        $typing = new TfUpdate([
            'account_id' => self::ACCOUNT,
            'seq' => 7,
            'position' => 0,
            'constructor' => 'updateChatUserTyping',
        ]);
        $typing->save();
        $typing->chat()->create([...$typing->childKey(), 'chat_id' => 22]);
        $typing->from()->create([...$typing->childKey(), 'peer_type' => 1, 'peer_id' => 501558149]);
        $typing->action()->create([...$typing->childKey(), 'constructor' => 'sendMessageUploadPhotoAction', 'progress' => 50]);

        $status = new TfUpdate([
            'account_id' => self::ACCOUNT,
            'seq' => 8,
            'position' => 0,
            'constructor' => 'updateUserStatus',
        ]);
        $status->save();
        $status->user()->create([...$status->childKey(), 'user_id' => 501558149]);
        $status->status()->create([...$status->childKey(), 'constructor' => 'userStatusOnline', 'expires' => 1750000000]);

        self::assertSame(22, (int) $typing->chat()->sole()->getAttribute('chat_id'));
        self::assertSame(50, (int) $typing->action()->sole()->getAttribute('progress'));
        self::assertSame('userStatusOnline', $status->status()->sole()->getAttribute('constructor'));
        self::assertSame(1750000000, (int) $status->status()->sole()->getAttribute('expires'));
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

        $participant = new TfChannelParticipant([
            'account_id' => self::ACCOUNT,
            'channel_id' => 999,
            'user_id' => 501558149,
            'constructor' => 'channelParticipantAdmin',
            'date' => 1750000000,
            'can_edit' => true,
        ]);
        $participant->save();
        $participant->promotedBy()->create([...$participant->childKey(), 'promoted_by' => 7]);
        $participant->rank()->create([...$participant->childKey(), 'rank' => 'owner']);
        $participant->subscriptionUntilDate()->create([...$participant->childKey(), 'subscription_until_date' => 1751000000]);

        $stored = TfChannelParticipant::forAccount(self::ACCOUNT)->sole();
        self::assertTrue((bool) $stored->getAttribute('can_edit'));
        self::assertSame('owner', $stored->rank()->sole()->getAttribute('rank'));
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
