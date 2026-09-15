<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Mirror\Models\TfAdminLogEvent;
use MeRezaRezaei\Teleframe\Mirror\Models\TfEncryptedChat;
use MeRezaRezaei\Teleframe\Mirror\Models\TfFolder;
use MeRezaRezaei\Teleframe\Mirror\Models\TfGroupCall;
use MeRezaRezaei\Teleframe\Mirror\Models\TfPhoneCall;
use MeRezaRezaei\Teleframe\Mirror\Models\TfQuickReply;
use MeRezaRezaei\Teleframe\Mirror\Models\TfSavedDialog;
use MeRezaRezaei\Teleframe\Mirror\Models\TfTheme;
use MeRezaRezaei\Teleframe\Mirror\Models\TfTodoItem;
use MeRezaRezaei\Teleframe\Mirror\Models\TfTodoList;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class MiscMirrorTest extends TestbenchTestCase
{
    private const MISC_TABLES = [
        'tf_folders',
        'tf_folders_photo',
        'tf_saved_dialogs',
        'tf_themes',
        'tf_themes_document',
        'tf_themes_settings',
        'tf_themes_emoticon',
        'tf_themes_installs_count',
        'tf_quick_replies',
        'tf_todo_items',
        'tf_todo_items_title',
        'tf_todo_lists',
        'tf_todo_lists_title',
        'tf_todo_lists_list',
        'tf_encrypted_chats',
        'tf_group_calls',
        'tf_phone_calls',
        'tf_phone_calls_protocol',
        'tf_phone_calls_receive_date',
        'tf_admin_log_events',
        'tf_admin_log_events_action',
    ];

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        AccountContext::reset();
        parent::tearDown();
    }

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function tempMiscDial(): string
    {
        $out = sys_get_temp_dir().'/tfmisc_'.uniqid();
        @mkdir($out, 0777, true);

        foreach (glob(dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_14_2000*.php') ?: [] as $file) {
            copy($file, $out.'/'.basename($file));
        }

        return $out;
    }

    private function migrateMisc(): void
    {
        $dial = $this->tempMiscDial();
        try {
            $this->artisan('migrate', ['--path' => $dial, '--realpath' => true])->assertExitCode(0);
        } finally {
            $this->rrmdir($dial);
        }
    }

    public function test_misc_dial_migrates_into_the_nf5_surface(): void
    {
        $this->migrateMisc();

        foreach (self::MISC_TABLES as $table) {
            self::assertTrue(Schema::hasTable($table), "{$table} must exist after the misc dial migrates");
        }

        self::assertTrue(Schema::hasColumns('tf_folders', ['account_id', 'id', 'title', 'autofill_new_broadcasts', 'autofill_public_groups', 'autofill_new_correspondents']));
        self::assertTrue(Schema::hasColumns('tf_folders_photo', ['account_id', 'id', 'constructor', 'has_video', 'photo_id', 'stripped_thumb', 'dc_id']));
        self::assertTrue(Schema::hasColumns('tf_saved_dialogs', ['account_id', 'constructor', 'peer_type', 'peer_id', 'top_message', 'pinned']));
        self::assertTrue(Schema::hasColumns('tf_themes', ['account_id', 'id', 'access_hash', 'slug', 'title', 'creator', 'default', 'for_chat']));
        self::assertTrue(Schema::hasColumns('tf_themes_settings', ['account_id', 'id', 'position', 'message_colors_animated', 'accent_color', 'outbox_accent_color']));
        self::assertTrue(Schema::hasColumns('tf_quick_replies', ['account_id', 'shortcut_id', 'shortcut', 'top_message', 'count']));
        self::assertTrue(Schema::hasColumns('tf_todo_lists', ['account_id', 'todo_list_id', 'others_can_append', 'others_can_complete']));
        self::assertTrue(Schema::hasColumns('tf_encrypted_chats', ['account_id', 'constructor', 'id', 'access_hash', 'date', 'admin_id', 'participant_id']));
        self::assertTrue(Schema::hasColumns('tf_admin_log_events', ['account_id', 'id', 'date', 'user_id']));
        self::assertTrue(Schema::hasColumns('tf_admin_log_events_action', ['account_id', 'id', 'constructor', 'prev_value', 'new_value', 'join_muted', 'via_chatlist', 'approved_by', 'user_id', 'prev_rank', 'new_rank']));
    }

    public function test_misc_tables_are_nf5_clean(): void
    {
        $this->migrateMisc();

        foreach (self::MISC_TABLES as $table) {
            $columns = DB::select("PRAGMA table_info({$table})");
            self::assertNotEmpty($columns, "{$table} must report its columns");

            foreach ($columns as $column) {
                self::assertSame(1, (int) $column->notnull, "[{$table}.{$column->name}] must be NOT NULL (no nullable columns)");
                $type = strtolower((string) $column->type);
                self::assertStringNotContainsString('json', $type, "[{$table}.{$column->name}] must not be a JSON column");
                self::assertStringNotContainsString('blob', $type, "[{$table}.{$column->name}] must not be a BLOB column");
            }
        }
    }

    public function test_multi_ctor_tables_carry_constructor_discriminators(): void
    {
        $this->migrateMisc();

        foreach (['tf_saved_dialogs', 'tf_encrypted_chats', 'tf_group_calls', 'tf_phone_calls'] as $multi) {
            self::assertTrue(Schema::hasColumn($multi, 'constructor'), "{$multi} must have a constructor discriminator");
        }

        foreach (['tf_folders', 'tf_themes', 'tf_quick_replies', 'tf_todo_items', 'tf_todo_lists', 'tf_admin_log_events'] as $single) {
            self::assertFalse(Schema::hasColumn($single, 'constructor'), "{$single} has a single ctor and must NOT carry a constructor discriminator");
        }
    }

    public function test_theme_round_trip_with_children(): void
    {
        $this->migrateMisc();
        AccountContext::set(42);

        $theme = TfTheme::create([
            'account_id' => 42,
            'id' => 3301,
            'access_hash' => 555,
            'slug' => 'night',
            'title' => 'Night',
            'creator' => true,
            'default' => false,
            'for_chat' => false,
        ]);

        $theme->document()->create(['account_id' => 42, 'document_id' => 9101]);
        $theme->settings()->create(['account_id' => 42, 'position' => 0, 'message_colors_animated' => 1, 'accent_color' => 0x1B1B1B, 'outbox_accent_color' => 0x7CB342]);
        $theme->settings()->create(['account_id' => 42, 'position' => 1, 'message_colors_animated' => 0, 'accent_color' => 0xFFFFFF, 'outbox_accent_color' => 0x000000]);
        $theme->emoticon()->create(['account_id' => 42, 'emoticon' => '🌙']);
        $theme->installsCount()->create(['account_id' => 42, 'installs_count' => 12345]);

        self::assertSame(2, $theme->settings()->count());
        self::assertSame([0x1B1B1B, 0xFFFFFF], $theme->settings()->orderBy('position')->pluck('accent_color')->all());
        self::assertSame(9101, $theme->document->document_id);
        self::assertSame('🌙', $theme->emoticon->emoticon);
        self::assertSame(12345, $theme->installsCount->installs_count);
        self::assertTrue($theme->creator);
        self::assertFalse($theme->default);
        self::assertSame('night', DB::table('tf_themes')->where('account_id', 42)->value('slug'));
    }

    public function test_admin_log_event_round_trip_with_action_child(): void
    {
        $this->migrateMisc();
        AccountContext::set(42);

        $event = TfAdminLogEvent::create([
            'account_id' => 42,
            'id' => 5501,
            'date' => 1726000001,
            'user_id' => 7001,
        ]);

        $event->action()->create([
            'account_id' => 42,
            'constructor' => 'channelAdminLogEventActionChangeTitle',
            'prev_value' => 'old',
            'new_value' => 'new',
        ]);

        $event = $event->fresh();
        self::assertSame(1726000001, $event->date);
        self::assertSame('channelAdminLogEventActionChangeTitle', $event->action->constructor);
        self::assertSame('old', $event->action->prev_value);
        self::assertSame('new', $event->action->new_value);

        $event->action()->update([
            'constructor' => 'channelAdminLogEventActionToggleNoForwards',
            'new_value' => '1',
        ]);
        self::assertSame('channelAdminLogEventActionToggleNoForwards', $event->fresh()->action->constructor);
    }

    public function test_todo_lists_and_items_round_trip(): void
    {
        $this->migrateMisc();
        AccountContext::set(42);

        $todoItem = TfTodoItem::create(['account_id' => 42, 'id' => 9001]);
        $todoItem->title()->create(['account_id' => 42, 'title' => 'Buy milk']);

        self::assertSame('Buy milk', $todoItem->title->title);

        $list = TfTodoList::create([
            'account_id' => 42,
            'todo_list_id' => 1,
            'others_can_append' => 1,
            'others_can_complete' => 0,
        ]);

        $list->title()->create([...$list->childKey(), 'title' => 'Groceries']);
        $list->list()->create([...$list->childKey(), 'position' => 0, 'id' => 9001, 'title' => 'Buy milk']);
        $list->list()->create([...$list->childKey(), 'position' => 1, 'id' => 2, 'title' => 'Buy eggs']);

        self::assertSame('Groceries', $list->title()->value('title'));
        self::assertSame([9001, 2], $list->list()->orderBy('position')->pluck('id')->all());
        self::assertTrue($list->others_can_append);
        self::assertFalse($list->others_can_complete);
    }

    public function test_quick_reply_and_saved_dialog_round_trip(): void
    {
        $this->migrateMisc();
        AccountContext::set(42);

        TfQuickReply::create([
            'account_id' => 42,
            'shortcut_id' => 10,
            'shortcut' => 'hi',
            'top_message' => 400,
            'count' => 3,
        ]);

        self::assertSame('hi', TfQuickReply::where('shortcut_id', 10)->value('shortcut'));

        TfSavedDialog::create([
            'account_id' => 42,
            'constructor' => 'savedDialog',
            'peer_type' => 2,
            'peer_id' => 7000,
            'top_message' => 99,
            'pinned' => 1,
        ]);

        self::assertSame(99, TfSavedDialog::where('peer_id', 7000)->value('top_message'));
        self::assertSame(1, DB::table('tf_saved_dialogs')->where('peer_id', 7000)->value('pinned'));
    }

    public function test_call_tables_round_trip(): void
    {
        $this->migrateMisc();
        AccountContext::set(42);

        $call = TfPhoneCall::create([
            'account_id' => 42,
            'constructor' => 'phoneCall',
            'id' => 2101,
            'access_hash' => 999,
            'date' => 1726000002,
            'admin_id' => 1,
            'participant_id' => 2,
            'video' => 0,
        ]);

        $call->protocol()->create(['account_id' => 42, 'udp_p2p' => 1, 'udp_reflector' => 0, 'min_layer' => 65, 'max_layer' => 120]);
        $call->receiveDate()->create(['account_id' => 42, 'receive_date' => 1726000100]);

        self::assertSame(65, $call->protocol->min_layer);
        self::assertFalse($call->protocol->udp_reflector);
        self::assertSame(1726000100, $call->receiveDate->receive_date);
        self::assertFalse($call->video);

        TfGroupCall::create(['account_id' => 42, 'constructor' => 'groupCall', 'id' => 2201, 'access_hash' => 888, 'duration' => 60]);
        self::assertSame(60, TfGroupCall::where('id', 2201)->value('duration'));

        TfEncryptedChat::create([
            'account_id' => 42,
            'constructor' => 'encryptedChat',
            'id' => 2301,
            'access_hash' => 777,
            'date' => 1726000003,
            'admin_id' => 1,
            'participant_id' => 2,
        ]);
        self::assertSame(777, TfEncryptedChat::where('id', 2301)->value('access_hash'));
    }

    public function test_folder_round_trip_with_photo_child(): void
    {
        $this->migrateMisc();
        AccountContext::set(42);

        $folder = TfFolder::create([
            'account_id' => 42,
            'id' => 3,
            'title' => 'Work',
            'autofill_new_broadcasts' => 1,
            'autofill_public_groups' => 0,
            'autofill_new_correspondents' => 1,
        ]);

        $folder->photo()->create(['account_id' => 42, 'constructor' => 'chatPhoto', 'has_video' => 0, 'photo_id' => 8101, 'stripped_thumb' => 'deadbeef', 'dc_id' => 2]);

        self::assertSame('Work', $folder->title);
        self::assertSame('chatPhoto', $folder->photo->constructor);
        self::assertSame(8101, $folder->photo->photo_id);
        self::assertSame('deadbeef', DB::table('tf_folders_photo')->where('id', 3)->value('stripped_thumb'));
    }

    public function test_account_isolation_across_misc_rows(): void
    {
        $this->migrateMisc();

        AccountContext::set(42);
        TfTheme::create(['account_id' => 42, 'id' => 1, 'access_hash' => 1, 'slug' => 'a', 'title' => 'A', 'creator' => false, 'default' => true, 'for_chat' => false]);

        AccountContext::set(7);
        TfTheme::create(['account_id' => 7, 'id' => 2, 'access_hash' => 2, 'slug' => 'b', 'title' => 'B', 'creator' => false, 'default' => false, 'for_chat' => true]);

        self::assertSame(1, TfTheme::all()->count(), 'global scope must isolate to the active account');
        self::assertSame(2, TfTheme::acrossAccounts()->count(), 'acrossAccounts must see every account');
        self::assertSame(1, TfTheme::forAccount(42)->count());
        self::assertSame(1, TfTheme::forAccount(42)->value('id'));
    }

    public function test_parent_delete_cascades_to_misc_children(): void
    {
        $this->migrateMisc();
        AccountContext::set(42);

        $theme = TfTheme::create(['account_id' => 42, 'id' => 7, 'access_hash' => 3, 'slug' => 'x', 'title' => 'X', 'creator' => false, 'default' => false, 'for_chat' => false]);
        $theme->document()->create(['account_id' => 42, 'document_id' => 1]);
        $theme->settings()->create(['account_id' => 42, 'position' => 0, 'message_colors_animated' => 0, 'accent_color' => 1, 'outbox_accent_color' => 2]);
        $theme->emoticon()->create(['account_id' => 42, 'emoticon' => 'm']);
        $theme->installsCount()->create(['account_id' => 42, 'installs_count' => 5]);

        self::assertSame(1, DB::table('tf_themes_document')->count());
        self::assertSame(1, DB::table('tf_themes_settings')->count());

        $theme->delete();

        self::assertSame(0, DB::table('tf_themes')->count());
        self::assertSame(0, DB::table('tf_themes_document')->count(), 'theme document must cascade with the parent');
        self::assertSame(0, DB::table('tf_themes_settings')->count(), 'theme settings must cascade with the parent');
        self::assertSame(0, DB::table('tf_themes_emoticon')->count(), 'theme emoticon must cascade with the parent');
        self::assertSame(0, DB::table('tf_themes_installs_count')->count(), 'theme installs_count must cascade with the parent');
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
