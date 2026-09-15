<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Mirror\Models\TfAttachMenuBot;
use MeRezaRezaei\Teleframe\Mirror\Models\TfBotApp;
use MeRezaRezaei\Teleframe\Mirror\Models\TfBotInfo;
use MeRezaRezaei\Teleframe\Mirror\Models\TfBotInlineResult;
use MeRezaRezaei\Teleframe\Mirror\Models\TfBusinessChatLink;
use MeRezaRezaei\Teleframe\Mirror\Models\TfSavedStarGift;
use MeRezaRezaei\Teleframe\Mirror\Models\TfStarsSubscription;
use MeRezaRezaei\Teleframe\Mirror\Models\TfStarsTransaction;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class StarsBusinessMirrorTest extends TestbenchTestCase
{
    private const STARS_BUSINESS_TABLES = [
        'tf_stars_transactions',
        'tf_stars_transactions_amount',
        'tf_stars_transactions_peer',
        'tf_stars_transactions_title',
        'tf_stars_transactions_description',
        'tf_stars_transactions_photo',
        'tf_stars_transactions_transaction_date',
        'tf_stars_transactions_transaction_url',
        'tf_stars_transactions_bot_payload',
        'tf_stars_transactions_msg_id',
        'tf_stars_transactions_extended_media',
        'tf_stars_transactions_subscription_period',
        'tf_stars_transactions_giveaway_post_id',
        'tf_stars_transactions_stargift',
        'tf_stars_transactions_floodskip_number',
        'tf_stars_transactions_starref_commission_permille',
        'tf_stars_transactions_starref_peer',
        'tf_stars_transactions_starref_amount',
        'tf_stars_transactions_paid_messages',
        'tf_stars_transactions_premium_gift_months',
        'tf_stars_transactions_ads_proceeds_from_date',
        'tf_stars_transactions_ads_proceeds_to_date',
        'tf_stars_subscriptions',
        'tf_stars_subscriptions_pricing',
        'tf_stars_subscriptions_chat_invite_hash',
        'tf_stars_subscriptions_title',
        'tf_stars_subscriptions_photo',
        'tf_stars_subscriptions_invoice_slug',
        'tf_saved_star_gifts',
        'tf_saved_star_gifts_gift',
        'tf_saved_star_gifts_from_id',
        'tf_saved_star_gifts_message',
        'tf_saved_star_gifts_msg_id',
        'tf_saved_star_gifts_saved_id',
        'tf_saved_star_gifts_convert_stars',
        'tf_saved_star_gifts_upgrade_stars',
        'tf_saved_star_gifts_can_export_at',
        'tf_saved_star_gifts_transfer_stars',
        'tf_saved_star_gifts_can_transfer_at',
        'tf_saved_star_gifts_can_resell_at',
        'tf_saved_star_gifts_collection_id',
        'tf_saved_star_gifts_prepaid_upgrade_hash',
        'tf_saved_star_gifts_drop_original_details_stars',
        'tf_saved_star_gifts_gift_num',
        'tf_saved_star_gifts_can_craft_at',
        'tf_business_chat_links',
        'tf_business_chat_links_entities',
        'tf_business_chat_links_title',
        'tf_attach_menu_bots',
        'tf_attach_menu_bots_icons',
        'tf_attach_menu_bots_peer_types',
        'tf_bot_apps',
        'tf_bot_apps_photo',
        'tf_bot_apps_document',
        'tf_bot_infos',
        'tf_bot_infos_user_id',
        'tf_bot_infos_description',
        'tf_bot_infos_description_photo',
        'tf_bot_infos_description_document',
        'tf_bot_infos_commands',
        'tf_bot_infos_menu_button',
        'tf_bot_infos_privacy_policy_url',
        'tf_bot_infos_app_settings',
        'tf_bot_infos_verifier_settings',
        'tf_bot_inline_results',
        'tf_bot_inline_results_send_message',
        'tf_bot_inline_results_title',
        'tf_bot_inline_results_description',
        'tf_bot_inline_results_url',
        'tf_bot_inline_results_thumb',
        'tf_bot_inline_results_content',
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

    private function migrateStarsBusiness(): void
    {
        $out = sys_get_temp_dir().'/tfstarsbiz_'.uniqid();
        @mkdir($out, 0777, true);

        foreach (glob(dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_14_2000*.php') ?: [] as $file) {
            copy($file, $out.'/'.basename($file));
        }

        try {
            $this->artisan('migrate', ['--path' => $out, '--realpath' => true])->assertExitCode(0);
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_all_stars_business_tables_migrate(): void
    {
        $this->migrateStarsBusiness();

        foreach (self::STARS_BUSINESS_TABLES as $table) {
            self::assertTrue(Schema::hasTable($table), "{$table} must exist after migration");
        }
    }

    public function test_nf5_schema_clean(): void
    {
        $this->migrateStarsBusiness();

        foreach (self::STARS_BUSINESS_TABLES as $tableName) {
            foreach (Schema::getColumns($tableName) as $col) {
                $type = strtolower((string) $col['type']);
                self::assertStringNotContainsString('json', $type, "{$tableName}.{$col['name']} must not be json");
                self::assertStringNotContainsString('blob', $type, "{$tableName}.{$col['name']} must not be blob");
                self::assertFalse((bool) $col['nullable'], "{$tableName}.{$col['name']} must be NOT NULL");
            }
        }
    }

    public function test_star_transaction_with_amount_and_peer(): void
    {
        $this->migrateStarsBusiness();

        $tx = TfStarsTransaction::create([
            'account_id' => 42,
            'id' => 'star_tx_001',
            'date' => 1726000000,
        ]);

        $tx->amount()->create([
            ...$tx->childKey(),
            'constructor' => 'starsAmount',
            'amount' => 500,
            'nanos' => 0,
        ]);

        $tx->peer()->create([
            ...$tx->childKey(),
            'constructor' => 'starsTransactionPeerUser',
            'peer_type' => 1,
            'peer_id' => 12345,
        ]);

        $tx->title()->create([...$tx->childKey(), 'title' => 'Gift stars']);
        $tx->description()->create([...$tx->childKey(), 'description' => 'Sent 500 stars']);

        $stored = TfStarsTransaction::forAccount(42)->sole();
        self::assertSame('star_tx_001', $stored->id);
        self::assertSame(500, (int) $stored->amount->getAttribute('amount'));
        self::assertSame('starsTransactionPeerUser', $stored->peer->getAttribute('constructor'));
        self::assertSame(12345, (int) $stored->peer->getAttribute('peer_id'));
        self::assertSame('Gift stars', $stored->title->title);
        self::assertSame('Sent 500 stars', $stored->description->description);
    }

    public function test_star_subscription_with_pricing(): void
    {
        $this->migrateStarsBusiness();

        $sub = TfStarsSubscription::create([
            'account_id' => 42,
            'id' => 'sub_abc',
            'peer_type' => 1,
            'peer_id' => 99,
            'until_date' => 1727000000,
            'canceled' => false,
            'can_refulfill' => true,
            'missing_balance' => false,
            'bot_canceled' => false,
        ]);

        $sub->pricing()->create([
            ...$sub->childKey(),
            'period' => 30,
            'amount' => 25,
        ]);

        $sub->title()->create([...$sub->childKey(), 'title' => 'Premium Bot']);

        $stored = TfStarsSubscription::forAccount(42)->sole();
        self::assertSame('sub_abc', $stored->id);
        self::assertSame(30, (int) $stored->pricing->period);
        self::assertSame(25, (int) $stored->pricing->amount);
        self::assertSame('Premium Bot', $stored->title->title);
        self::assertFalse($stored->canceled);
        self::assertTrue($stored->can_refulfill);
    }

    public function test_saved_star_gift_with_gift_and_collection_ids(): void
    {
        $this->migrateStarsBusiness();

        $gift = TfSavedStarGift::create([
            'account_id' => 42,
            'id' => 44444,
            'date' => 1726000000,
            'name_hidden' => true,
            'unsaved' => false,
            'refunded' => false,
            'can_upgrade' => true,
            'pinned_to_top' => false,
            'upgrade_separate' => false,
        ]);

        $gift->gift()->create([
            ...$gift->childKey(),
            'constructor' => 'starGift',
            'gift_id' => 555,
            'stars' => 100,
        ]);

        $gift->collectionIds()->create([...$gift->childKey(), 'position' => 0, 'collection_id' => 1]);
        $gift->collectionIds()->create([...$gift->childKey(), 'position' => 1, 'collection_id' => 7]);

        $gift->convertStars()->create([...$gift->childKey(), 'convert_stars' => 95]);

        $stored = TfSavedStarGift::forAccount(42)->sole();
        self::assertSame(44444, $stored->id);
        self::assertSame('starGift', $stored->gift->getAttribute('constructor'));
        self::assertSame(100, (int) $stored->gift->getAttribute('stars'));
        self::assertSame([1, 7], $stored->collectionIds()->orderBy('position')->pluck('collection_id')->all());
        self::assertSame(95, (int) $stored->convertStars->convert_stars);
        self::assertTrue($stored->name_hidden);
        self::assertTrue($stored->can_upgrade);
    }

    public function test_business_chat_link_with_entities_and_title(): void
    {
        $this->migrateStarsBusiness();

        $link = TfBusinessChatLink::create([
            'account_id' => 42,
            'link' => 'https://t.me/+abc123',
            'message' => 'Contact us',
            'views' => 10,
        ]);

        $link->entities()->create([
            ...$link->childKey(),
            'position' => 0,
            'constructor' => 'messageEntityUrl',
            'offset' => 0,
            'length' => 11,
        ]);

        $link->title()->create([...$link->childKey(), 'title' => 'Support']);

        $stored = TfBusinessChatLink::forAccount(42)->sole();
        self::assertSame('https://t.me/+abc123', $stored->link);
        self::assertSame(10, (int) $stored->views);
        self::assertSame('messageEntityUrl', $stored->entities()->sole()->getAttribute('constructor'));
        self::assertSame('Support', $stored->title->title);
    }

    public function test_account_scoping_isolation(): void
    {
        $this->migrateStarsBusiness();

        AccountContext::set(42);
        TfStarsTransaction::create(['account_id' => 42, 'id' => 'tx_a', 'date' => 1726000000]);

        AccountContext::set(7);
        TfStarsTransaction::create(['account_id' => 7, 'id' => 'tx_b', 'date' => 1726000001]);

        self::assertSame(1, TfStarsTransaction::all()->count());
        self::assertSame(2, TfStarsTransaction::acrossAccounts()->count());
        self::assertSame('tx_a', TfStarsTransaction::forAccount(42)->sole()->id);
    }

    public function test_attach_menu_bot_with_peer_types(): void
    {
        $this->migrateStarsBusiness();

        $bot = TfAttachMenuBot::create([
            'account_id' => 42,
            'bot_id' => 777,
            'short_name' => 'TestBot',
            'inactive' => false,
            'has_settings' => true,
            'request_write_access' => false,
            'show_in_attach_menu' => true,
            'show_in_side_menu' => false,
            'side_menu_disclaimer_needed' => false,
        ]);

        $bot->peerTypes()->create([
            ...$bot->childKey(),
            'position' => 0,
            'constructor' => 'attachMenuPeerTypeBotPM',
        ]);

        $bot->peerTypes()->create([
            ...$bot->childKey(),
            'position' => 1,
            'constructor' => 'attachMenuPeerTypeChat',
        ]);

        $stored = TfAttachMenuBot::forAccount(42)->sole();
        self::assertSame(777, (int) $stored->bot_id);
        self::assertSame('TestBot', $stored->short_name);
        self::assertSame(2, $stored->peerTypes()->count());
        self::assertSame(
            ['attachMenuPeerTypeBotPM', 'attachMenuPeerTypeChat'],
            $stored->peerTypes()->orderBy('position')->pluck('constructor')->all()
        );
    }

    public function test_bot_app_with_photo_and_document(): void
    {
        $this->migrateStarsBusiness();

        $app = TfBotApp::create([
            'account_id' => 42,
            'constructor' => 'botApp',
            'id' => 888,
            'access_hash' => 1111,
            'short_name' => 'myapp',
            'title' => 'My App',
            'description' => 'An app',
            'hash' => 9999,
        ]);

        $app->photo()->create([
            ...$app->childKey(),
            'constructor' => 'photo',
            'access_hash' => 222,
            'file_reference' => 'aabb',
            'date' => 1726000000,
            'dc_id' => 2,
            'has_stickers' => false,
        ]);

        $app->document()->create([
            ...$app->childKey(),
            'constructor' => 'document',
            'access_hash' => 333,
            'file_reference' => 'ccdd',
            'date' => 1726000001,
            'mime_type' => 'application/octet-stream',
            'size' => 4096,
            'dc_id' => 2,
        ]);

        $stored = TfBotApp::forAccount(42)->sole();
        self::assertSame('myapp', $stored->short_name);
        self::assertSame('photo', $stored->photo->getAttribute('constructor'));
        self::assertSame(222, (int) $stored->photo->getAttribute('access_hash'));
        self::assertSame('document', $stored->document->getAttribute('constructor'));
        self::assertSame(4096, (int) $stored->document->getAttribute('size'));
    }

    public function test_bot_info_with_commands_and_menu_button(): void
    {
        $this->migrateStarsBusiness();

        $info = TfBotInfo::create([
            'account_id' => 42,
            'id' => 5000,
            'has_preview_medias' => true,
        ]);

        $info->userId()->create([...$info->childKey(), 'user_id' => 5000]);
        $info->description()->create([...$info->childKey(), 'description' => 'I am a bot']);

        $info->commands()->create([...$info->childKey(), 'position' => 0, 'command' => 'start', 'description' => 'Begin']);
        $info->commands()->create([...$info->childKey(), 'position' => 1, 'command' => 'help', 'description' => 'Assist']);

        $info->menuButton()->create([
            ...$info->childKey(),
            'constructor' => 'botMenuButton',
            'text' => 'Menu',
            'url' => '',
        ]);

        $info->appSettings()->create([
            ...$info->childKey(),
            'placeholder_path' => '',
            'background_color' => 0,
            'background_dark_color' => 0,
            'header_color' => 0,
            'header_dark_color' => 0,
        ]);

        $stored = TfBotInfo::forAccount(42)->sole();
        self::assertSame(5000, (int) $stored->id);
        self::assertSame('I am a bot', $stored->description->description);
        self::assertSame(2, $stored->commands()->count());
        self::assertSame('start', $stored->commands()->orderBy('position')->first()->command);
        self::assertSame('botMenuButton', $stored->menuButton->getAttribute('constructor'));
        self::assertTrue($stored->has_preview_medias);
    }

    public function test_bot_inline_result_with_send_message(): void
    {
        $this->migrateStarsBusiness();

        $result = TfBotInlineResult::create([
            'account_id' => 42,
            'constructor' => 'botInlineResult',
            'id' => 'inline_xyz',
            'type' => 'photo',
        ]);

        $result->sendMessage()->create([
            ...$result->childKey(),
            'constructor' => 'botInlineMessageMediaAuto',
            'no_webpage' => false,
            'invert_media' => false,
            'shipping_address_requested' => false,
            'test' => false,
            'force_large_media' => false,
            'force_small_media' => false,
            'manual' => false,
            'safe' => false,
            'message' => 'Check this out',
            'title' => 'Photo',
        ]);

        $result->title()->create([...$result->childKey(), 'title' => 'My Photo']);
        $result->description()->create([...$result->childKey(), 'description' => 'A nice photo']);

        $stored = TfBotInlineResult::forAccount(42)->sole();
        self::assertSame('inline_xyz', $stored->id);
        self::assertSame('botInlineMessageMediaAuto', $stored->sendMessage->getAttribute('constructor'));
        self::assertSame('Check this out', $stored->sendMessage->getAttribute('message'));
        self::assertSame('My Photo', $stored->title->title);
        self::assertSame('A nice photo', $stored->description->description);
    }

    public function test_string_id_star_transaction_persists(): void
    {
        $this->migrateStarsBusiness();

        $tx = TfStarsTransaction::create([
            'account_id' => 42,
            'id' => 'opaque_server_string_12345',
            'date' => 1726000000,
            'gift' => true,
            'pending' => true,
        ]);

        $stored = TfStarsTransaction::where('id', 'opaque_server_string_12345')->firstOrFail();
        self::assertSame('opaque_server_string_12345', $stored->id);
        self::assertTrue($stored->gift);
        self::assertTrue($stored->pending);
        self::assertFalse($stored->refund);
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
