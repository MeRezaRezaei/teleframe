<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChat;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialog;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialogsPts;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUser;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUsersUsername;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * NF5 identity-domain mirror test (plan Task 2): the four hand-authored
 * tf_users / tf_chats / tf_channels / tf_dialogs dials are picked up by the
 * UpdateIngestor seam, stay NF5-clean (all 51 tables: zero nullable, no
 * json/blob), and round-trip the flagged wire facts through the hand-authored
 * models with composite keys + account scoping.
 */
final class IdentityMirrorTest extends IngestTestCase
{
    private const ACCOUNT = 9;

    private const OTHER_ACCOUNT = 10;

    private const IDENTITY_TABLES = [
        // users (parent + 19 children)
        'tf_users',
        'tf_users_access_hash',
        'tf_users_first_name',
        'tf_users_last_name',
        'tf_users_username',
        'tf_users_phone',
        'tf_users_photo',
        'tf_users_status',
        'tf_users_bot_info_version',
        'tf_users_restriction_reason',
        'tf_users_bot_inline_placeholder',
        'tf_users_lang_code',
        'tf_users_emoji_status',
        'tf_users_usernames',
        'tf_users_stories_max_id',
        'tf_users_color',
        'tf_users_profile_color',
        'tf_users_bot_active_users',
        'tf_users_bot_verification_icon',
        'tf_users_send_paid_messages_stars',
        // chats (parent + 4 children)
        'tf_chats',
        'tf_chats_photo',
        'tf_chats_migrated_to',
        'tf_chats_admin_rights',
        'tf_chats_default_banned_rights',
        // channels (parent + 18 children)
        'tf_channels',
        'tf_channels_username',
        'tf_channels_photo',
        'tf_channels_restriction_reason',
        'tf_channels_admin_rights',
        'tf_channels_banned_rights',
        'tf_channels_default_banned_rights',
        'tf_channels_participants_count',
        'tf_channels_usernames',
        'tf_channels_stories_max_id',
        'tf_channels_color',
        'tf_channels_profile_color',
        'tf_channels_emoji_status',
        'tf_channels_level',
        'tf_channels_subscription_until_date',
        'tf_channels_bot_verification_icon',
        'tf_channels_send_paid_messages_stars',
        'tf_channels_linked_monoforum_id',
        'tf_channels_until_date',
        // dialogs (parent + 6 children)
        'tf_dialogs',
        'tf_dialogs_notify_settings',
        'tf_dialogs_pts',
        'tf_dialogs_draft',
        'tf_dialogs_folder_id',
        'tf_dialogs_ttl_period',
        'tf_dialogs_folder',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([self::ACCOUNT, self::OTHER_ACCOUNT] as $id) {
            DB::table('telegram_accounts')->insert([
                'id' => $id,
                'label' => 'identity-mirror-'.$id,
                'type' => 'user',
                'dc_id' => 2,
            ]);
        }
    }

    public function test_identity_mirror_schema_is_nf5_clean(): void
    {
        self::assertCount(51, self::IDENTITY_TABLES);

        foreach (self::IDENTITY_TABLES as $tableName) {
            self::assertTrue(Schema::hasTable($tableName), "{$tableName} missing");
            foreach (Schema::getColumns($tableName) as $col) {
                $type = strtolower((string) $col['type']);
                self::assertStringNotContainsString('json', $type, "{$tableName}.{$col['name']} must not be json");
                self::assertStringNotContainsString('blob', $type, "{$tableName}.{$col['name']} must not be blob");
                self::assertFalse((bool) $col['nullable'], "{$tableName}.{$col['name']} must be NOT NULL");
            }
        }
    }

    public function test_user_row_round_trips_with_fact_children(): void
    {
        $user = new TfUser([
            'account_id' => self::ACCOUNT,
            'id' => 501558149,
            'constructor' => 'user',
            'contact' => true,
            'premium' => true,
            'bot' => false,
        ]);
        $user->save();

        $user->firstName()->create([...$user->childKey(), 'first_name' => 'Ardavan']);
        $user->username()->create([...$user->childKey(), 'username' => 'ardavanz']);
        $user->photo()->create([...$user->childKey(),
            'constructor' => 'chatPhoto',
            'has_video' => true,
            'photo_id' => 999,
            'stripped_thumb' => '0a1b2c',
            'dc_id' => 2,
        ]);
        $user->usernames()->create([...$user->childKey(), 'position' => 0, 'constructor' => 'username', 'editable' => true, 'active' => true, 'username' => 'ardavanz']);
        $user->usernames()->create([...$user->childKey(), 'position' => 1, 'constructor' => 'username', 'editable' => false, 'active' => false, 'username' => 'ardavanz_bot']);

        $stored = TfUser::forAccount(self::ACCOUNT)->sole();
        self::assertSame('user', $stored->getAttribute('constructor'));
        self::assertTrue((bool) $stored->getAttribute('contact'));
        self::assertTrue((bool) $stored->getAttribute('premium'));
        self::assertFalse((bool) $stored->getAttribute('bot'));

        self::assertSame('Ardavan', $stored->firstName()->sole()->getAttribute('first_name'));
        $thumb = $stored->photo()->sole();
        self::assertSame('0a1b2c', $thumb->getAttribute('stripped_thumb'));
        self::assertSame(999, (int) $thumb->getAttribute('photo_id'));

        $handles = $stored->usernames()
            ->orderBy('position')
            ->pluck('username')
            ->all();
        self::assertSame(['ardavanz', 'ardavanz_bot'], $handles);
    }

    public function test_chat_row_round_trips_admin_rights_fact(): void
    {
        $chat = new TfChat([
            'account_id' => self::ACCOUNT,
            'id' => 220000013,
            'constructor' => 'chat',
            'title' => 'Park Bench',
            'noforwards' => true,
        ]);
        $chat->save();

        $chat->adminRights()->create([...$chat->childKey(), 'constructor' => 'chatAdminRights', 'ban_users' => true, 'post_messages' => true]);
        $chat->defaultBannedRights()->create([...$chat->childKey(), 'constructor' => 'chatBannedRights', 'send_messages' => true, 'until_date' => 0]);

        $stored = TfChat::forAccount(self::ACCOUNT)->sole();
        self::assertSame('Park Bench', $stored->getAttribute('title'));
        $rights = $stored->adminRights()->sole();
        self::assertTrue((bool) $rights->getAttribute('ban_users'));
        self::assertFalse((bool) $rights->getAttribute('manage_call'));
    }

    public function test_channel_row_round_trips_negative_id_and_children(): void
    {
        $channel = new TfChannel([
            'account_id' => self::ACCOUNT,
            'id' => -1002000111,
            'constructor' => 'channel',
            'title' => 'Harbor Radio',
            'megagroup' => false,
            'broadcast' => true,
        ]);
        $channel->save();

        $channel->username()->create([...$channel->childKey(), 'username' => 'harbor_radio']);
        $channel->participantsCount()->create([...$channel->childKey(), 'participants_count' => 1042]);
        $channel->restrictionReasons()->create([...$channel->childKey(), 'position' => 0, 'platform' => 'ios', 'reason' => 'porn', 'text' => '#Pornography']);

        $stored = TfChannel::forAccount(self::ACCOUNT)->sole();
        self::assertSame(-1002000111, (int) $stored->getAttribute('id'));
        self::assertTrue((bool) $stored->getAttribute('broadcast'));
        self::assertSame('harbor_radio', $stored->username()->sole()->getAttribute('username'));
        self::assertSame(1042, (int) $stored->participantsCount()->sole()->getAttribute('participants_count'));
        self::assertSame('ios', $stored->restrictionReasons()->sole()->getAttribute('platform'));
    }

    public function test_dialog_peer_pair_key_round_trips_with_children(): void
    {
        $dialog = new TfDialog([
            'account_id' => self::ACCOUNT,
            'peer_type' => 3,
            'peer_id' => -1002000111,
            'constructor' => 'dialog',
            'top_message' => 77,
            'unread_count' => 2,
            'pinned' => true,
        ]);
        $dialog->save();

        $dialog->notifySettings()->create([...$dialog->childKey(), 'constructor' => 'peerNotifySettings', 'silent' => true, 'mute_until' => 0]);
        $dialog->pts()->create([...$dialog->childKey(), 'pts' => 1400]);
        $dialog->folderId()->create([...$dialog->childKey(), 'folder_id' => 1]);

        $stored = TfDialog::forAccount(self::ACCOUNT)->sole();
        self::assertSame(3, (int) $stored->getAttribute('peer_type'));
        self::assertSame(-1002000111, (int) $stored->getAttribute('peer_id'));
        self::assertSame(77, (int) $stored->getAttribute('top_message'));
        self::assertTrue((bool) $stored->getAttribute('pinned'));
        self::assertSame(1400, (int) $stored->pts()->sole()->getAttribute('pts'));
        self::assertTrue((bool) $stored->notifySettings()->sole()->getAttribute('silent'));

        $back = TfDialogsPts::first();
        self::assertNotNull($back);
        self::assertSame(3, (int) $back->getAttribute('peer_type'));
        self::assertSame(-1002000111, (int) $back->dialog()->sole()->getAttribute('peer_id'));
    }

    public function test_composite_child_key_is_account_scoped_across_accounts(): void
    {
        foreach ([self::ACCOUNT, self::OTHER_ACCOUNT] as $accountId) {
            $user = new TfUser([
                'account_id' => $accountId,
                'id' => 501558149,
                'constructor' => 'user',
            ]);
            $user->save();
            $user->username()->create([...$user->childKey(), 'username' => "user_{$accountId}"]);
        }

        self::assertSame(1, TfUser::forAccount(self::ACCOUNT)->count());
        self::assertSame(2, TfUser::acrossAccounts()->count());
        self::assertSame(2, TfUsersUsername::acrossAccounts()->count());

        // Relation traversal is active under AccountContext, the sibling
        // messages domain's established idiom for account scoping (global
        // AccountScope). Two accounts sharing one telegram id must each see
        // only their own child rows.
        self::assertSame(
            'user_'.self::ACCOUNT,
            AccountContext::for(self::ACCOUNT, fn () => TfUser::query()->sole()->username()->sole()->getAttribute('username')),
        );
        self::assertSame(
            'user_'.self::OTHER_ACCOUNT,
            AccountContext::for(self::OTHER_ACCOUNT, fn () => TfUser::query()->sole()->username()->sole()->getAttribute('username')),
        );
    }
}
