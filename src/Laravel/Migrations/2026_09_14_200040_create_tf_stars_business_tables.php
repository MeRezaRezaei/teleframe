<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — stars/business domain part 1 (plan Task 6): stars transactions,
 * stars subscriptions, saved star gifts, business chat links.
 *
 * Hand-authored against TL_telegram_v227.tl + the 2026-09-11 mirror catalog:
 *
 * - tf_stars_transactions     — starsTransaction ctor. id is the TL `string`
 *   id (a server-issued opaque handle, not a numeric msg id) — key column is
 *   TEXT. amount (StarsAmount) and peer (StarsTransactionPeer) are REQUIRED
 *   object facts, not flag-gated: both are constructor-discriminated flattened
 *   1:1 children (StarsAmount = starsAmount|starsTonAmount; StarsTransactionPeer
 *   = 8 empty ctors + the peer-carrying starsTransactionPeer). The catalog's
 *   `base` FK→ entries therefore materialize as 1:1 child rows, never inline
 *   objects (the flat write path has no nested rows).
 * - tf_stars_subscriptions    — starsSubscription ctor; id is a TL `string`.
 *   peer is a REQUIRED Peer → inline {peer_type, peer_id}. pricing is a
 *   required single-ctor StarsSubscriptionPricing → flat 1:1 child.
 * - tf_saved_star_gifts       — savedStarGift ctor. The TL ctor carries no own
 *   id; the parent key id is the inner StarGift `id` (long). gift is the
 *   REQUIRED StarGift union (starGift|starGiftUnique) flattened as the
 *   1:1 tf_saved_star_gifts_gift child; its inner `id` field is dropped
 *   because it is the parent key (plain-name collision under the flat path).
 * - tf_business_chat_links    — businessChatLink ctor. The link string IS the
 *   Telegram-supplied key (catalog `base` leads with `link`) — key column is
 *   TEXT `link`; every child is keyed (account_id, link).
 *
 * Shared flatten rules (same as the messages/media domains):
 * - flags.?true → BOOLEAN NOT NULL DEFAULT FALSE (catalog `bools`).
 * - Optional/flag-gated scalar or object facts → 1:1 child (row exists = fact
 *   exists; union object children carry their own constructor column).
 * - Vector<X> → 1:N child keyed (account_id, key[, position]).
 * - bytes → lowercase hex TEXT. No nullable, no json/blob, no auto-increment,
 *   no timestamps.
 * - Nested objects under a flattened union (sticker:Document, background:
 *   StarGiftBackground, peer_color:PeerColor, attributes:Vector<StarGiftAttribute>,
 *   resell_amount:Vector<StarsAmount>, TextWithEntities.entities, WebDocument
 *   attributes, MessageEntity/MessageMedia nested payloads, StarGiftAttribute*,
 *   BusinessBotRights, BusinessWorkHours, ...) cannot nest under the flat
 *   decomposer write path → deferred; the discriminator + immediate flat
 *   fields are the mirror truth here.
 * - No FK constraints are declared (messages-domain style): cross-domain and
 *   intra-domain FK wiring is the Task-8 299999 migration after the worktrees
 *   merge. Eloquent relations carry the graph meanwhile.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createStarsTransactions();
        $this->createStarsSubscriptions();
        $this->createSavedStarGifts();
        $this->createBusinessChatLinks();
    }

    public function down(): void
    {
        foreach (array_reverse(self::BUSINESS_CHAT_LINK_CHILDREN) as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_business_chat_links');

        foreach (array_reverse(self::SAVED_STAR_GIFT_CHILDREN) as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_saved_star_gifts');

        foreach (array_reverse(self::STARS_SUBSCRIPTION_CHILDREN) as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_stars_subscriptions');

        foreach (array_reverse(self::STARS_TRANSACTION_CHILDREN) as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_stars_transactions');
    }

    private const STARS_TRANSACTION_CHILDREN = [
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
    ];

    private const STARS_SUBSCRIPTION_CHILDREN = [
        'tf_stars_subscriptions_pricing',
        'tf_stars_subscriptions_chat_invite_hash',
        'tf_stars_subscriptions_title',
        'tf_stars_subscriptions_photo',
        'tf_stars_subscriptions_invoice_slug',
    ];

    private const SAVED_STAR_GIFT_CHILDREN = [
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
    ];

    private const BUSINESS_CHAT_LINK_CHILDREN = [
        'tf_business_chat_links_entities',
        'tf_business_chat_links_title',
    ];

    private function createStarsTransactions(): void
    {
        Schema::create('tf_stars_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('id');
            $table->integer('date');
            $table->boolean('refund')->default(false);
            $table->boolean('pending')->default(false);
            $table->boolean('failed')->default(false);
            $table->boolean('gift')->default(false);
            $table->boolean('reaction')->default(false);
            $table->boolean('stargift_upgrade')->default(false);
            $table->boolean('business_transfer')->default(false);
            $table->boolean('stargift_resale')->default(false);
            $table->boolean('posts_search')->default(false);
            $table->boolean('stargift_prepaid_upgrade')->default(false);
            $table->boolean('stargift_drop_original_details')->default(false);
            $table->boolean('phonegroup_message')->default(false);
            $table->boolean('stargift_auction_bid')->default(false);
            $table->boolean('offer')->default(false);

            $table->primary(['account_id', 'id']);
        });

        // amount — REQUIRED StarsAmount (starsAmount|starsTonAmount), flatted.
        Schema::create('tf_stars_transactions_amount', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->string('constructor', 64);
            $table->bigInteger('amount')->default(0);
            $table->integer('nanos')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // peer — REQUIRED StarsTransactionPeer union; only the
        // starsTransactionPeer ctor carries the Peer payload.
        Schema::create('tf_stars_transactions_peer', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->string('constructor', 64);
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_title', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->text('title');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_description', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->text('description');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_photo', function (Blueprint $table) {
            self::factKey($table, 'id');
            self::webDocumentColumns($table);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_transaction_date', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('transaction_date');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_transaction_url', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->text('transaction_url');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_bot_payload', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->text('bot_payload');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_msg_id', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('msg_id');
            $table->primary(['account_id', 'id']);
        });

        // extended_media — Vector<MessageMedia>, 1:N by vector slot. Same
        // flat MessageMedia union as tf_messages_media (messages domain).
        Schema::create('tf_stars_transactions_extended_media', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->smallInteger('position');
            self::messageMediaColumns($table);
            $table->primary(['account_id', 'id', 'position']);
            $table->index(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_subscription_period', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('subscription_period');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_giveaway_post_id', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('giveaway_post_id');
            $table->primary(['account_id', 'id']);
        });

        // stargift — flag.14?StarGift union, flattened; inner StarGift id is
        // deferred (the child id column is the transaction key).
        Schema::create('tf_stars_transactions_stargift', function (Blueprint $table) {
            self::factKey($table, 'id');
            self::starGiftColumns($table);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_floodskip_number', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('floodskip_number');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_starref_commission_permille', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('starref_commission_permille');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_starref_peer', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->tinyInteger('starref_peer_type')->default(0);
            $table->bigInteger('starref_peer_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_starref_amount', function (Blueprint $table) {
            self::factKey($table, 'id');
            self::starsAmountColumns($table);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_paid_messages', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('paid_messages');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_premium_gift_months', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('premium_gift_months');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_ads_proceeds_from_date', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('ads_proceeds_from_date');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_ads_proceeds_to_date', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('ads_proceeds_to_date');
            $table->primary(['account_id', 'id']);
        });
    }

    private function createStarsSubscriptions(): void
    {
        Schema::create('tf_stars_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('until_date');
            $table->boolean('canceled')->default(false);
            $table->boolean('can_refulfill')->default(false);
            $table->boolean('missing_balance')->default(false);
            $table->boolean('bot_canceled')->default(false);

            $table->primary(['account_id', 'id']);
            $table->index(['account_id', 'peer_type', 'peer_id']);
        });

        // pricing — REQUIRED single-ctor StarsSubscriptionPricing (no ctor col).
        Schema::create('tf_stars_subscriptions_pricing', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('period');
            $table->bigInteger('amount');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_chat_invite_hash', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->text('chat_invite_hash');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_title', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->text('title');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_photo', function (Blueprint $table) {
            self::factKey($table, 'id');
            self::webDocumentColumns($table);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_invoice_slug', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->text('invoice_slug');
            $table->primary(['account_id', 'id']);
        });
    }

    private function createSavedStarGifts(): void
    {
        Schema::create('tf_saved_star_gifts', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('date');
            $table->boolean('name_hidden')->default(false);
            $table->boolean('unsaved')->default(false);
            $table->boolean('refunded')->default(false);
            $table->boolean('can_upgrade')->default(false);
            $table->boolean('pinned_to_top')->default(false);
            $table->boolean('upgrade_separate')->default(false);

            $table->primary(['account_id', 'id']);
        });

        // gift — REQUIRED StarGift union (starGift|starGiftUnique), flattened;
        // inner StarGift id is the parent key (documented defer).
        Schema::create('tf_saved_star_gifts_gift', function (Blueprint $table) {
            self::factKey($table, 'id');
            self::starGiftColumns($table);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_from_id', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->tinyInteger('from_id_type')->default(0);
            $table->bigInteger('from_id_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // message — single-ctor TextWithEntities flattened to its text;
        // entities (Vector<MessageEntity>) deferred to the messages domain.
        Schema::create('tf_saved_star_gifts_message', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->text('message');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_msg_id', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('msg_id');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_saved_id', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->bigInteger('saved_id');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_convert_stars', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->bigInteger('convert_stars');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_upgrade_stars', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->bigInteger('upgrade_stars');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_can_export_at', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('can_export_at');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_transfer_stars', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->bigInteger('transfer_stars');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_can_transfer_at', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('can_transfer_at');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_can_resell_at', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('can_resell_at');
            $table->primary(['account_id', 'id']);
        });

        // collection_id — Vector<int>, 1:N by vector slot.
        Schema::create('tf_saved_star_gifts_collection_id', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->smallInteger('position');
            $table->integer('collection_id');
            $table->primary(['account_id', 'id', 'position']);
            $table->index(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_prepaid_upgrade_hash', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->text('prepaid_upgrade_hash');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_drop_original_details_stars', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->bigInteger('drop_original_details_stars');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_gift_num', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('gift_num');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_saved_star_gifts_can_craft_at', function (Blueprint $table) {
            self::factKey($table, 'id');
            $table->integer('can_craft_at');
            $table->primary(['account_id', 'id']);
        });
    }

    private function createBusinessChatLinks(): void
    {
        Schema::create('tf_business_chat_links', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('link');
            $table->text('message');
            $table->integer('views');

            $table->primary(['account_id', 'link']);
        });

        // entities — Vector<MessageEntity>, 1:N by vector slot. Same flat union
        // as tf_messages_entities (messages domain).
        Schema::create('tf_business_chat_links_entities', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('link');
            $table->smallInteger('position');
            $table->string('constructor', 64);
            $table->integer('offset');
            $table->integer('length');
            $table->text('language')->default('');
            $table->text('url')->default('');
            $table->bigInteger('user_id')->default(0);
            $table->bigInteger('document_id')->default(0);
            $table->boolean('collapsed')->default(false);
            $table->text('old_text')->default('');
            $table->integer('date')->default(0);
            $table->boolean('relative')->default(false);
            $table->boolean('short_time')->default(false);
            $table->boolean('long_time')->default(false);
            $table->boolean('short_date')->default(false);
            $table->boolean('long_date')->default(false);
            $table->boolean('day_of_week')->default(false);
            $table->primary(['account_id', 'link', 'position']);
            $table->index(['account_id', 'link']);
        });

        Schema::create('tf_business_chat_links_title', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('link');
            $table->text('title');
            $table->primary(['account_id', 'link']);
        });
    }

    private static function factKey(Blueprint $table, string $key): void
    {
        $table->unsignedBigInteger('account_id');
        $table->text($key);
    }

    private static function webDocumentColumns(Blueprint $table): void
    {
        $table->string('constructor', 64);
        $table->text('url');
        $table->bigInteger('access_hash')->default(0);
        $table->integer('size')->default(0);
        $table->text('mime_type')->default('');
    }

    private static function starsAmountColumns(Blueprint $table): void
    {
        $table->string('constructor', 64);
        $table->bigInteger('amount')->default(0);
        $table->integer('nanos')->default(0);
    }

    /**
     * Flat column set of the StarGift union (starGift | starGiftUnique).
     * Inner `id` is dropped: it is the saved-gift parent key or the child fk
     * column (plain-name collision under the flat path).
     */
    private static function starGiftColumns(Blueprint $table): void
    {
        $table->string('constructor', 64);
        $table->boolean('limited')->default(false);
        $table->boolean('sold_out')->default(false);
        $table->boolean('birthday')->default(false);
        $table->boolean('require_premium')->default(false);
        $table->boolean('limited_per_user')->default(false);
        $table->boolean('peer_color_available')->default(false);
        $table->boolean('auction')->default(false);
        $table->boolean('resale_ton_only')->default(false);
        $table->boolean('theme_available')->default(false);
        $table->boolean('burned')->default(false);
        $table->boolean('crafted')->default(false);
        $table->bigInteger('gift_id')->default(0);
        $table->bigInteger('stars')->default(0);
        $table->integer('availability_remains')->default(0);
        $table->integer('availability_total')->default(0);
        $table->bigInteger('availability_resale')->default(0);
        $table->bigInteger('convert_stars')->default(0);
        $table->integer('first_sale_date')->default(0);
        $table->integer('last_sale_date')->default(0);
        $table->bigInteger('upgrade_stars')->default(0);
        $table->bigInteger('resell_min_stars')->default(0);
        $table->text('title')->default('');
        $table->tinyInteger('released_by_type')->default(0);
        $table->bigInteger('released_by_id')->default(0);
        $table->integer('per_user_total')->default(0);
        $table->integer('per_user_remains')->default(0);
        $table->integer('locked_until_date')->default(0);
        $table->text('auction_slug')->default('');
        $table->integer('gifts_per_round')->default(0);
        $table->integer('auction_start_date')->default(0);
        $table->integer('upgrade_variants')->default(0);
        $table->text('slug')->default('');
        $table->integer('num')->default(0);
        $table->tinyInteger('owner_id_type')->default(0);
        $table->bigInteger('owner_id_id')->default(0);
        $table->text('owner_name')->default('');
        $table->text('owner_address')->default('');
        $table->text('gift_address')->default('');
        $table->bigInteger('value_amount')->default(0);
        $table->text('value_currency')->default('');
        $table->bigInteger('value_usd_amount')->default(0);
        $table->tinyInteger('theme_peer_type')->default(0);
        $table->bigInteger('theme_peer_id')->default(0);
        $table->integer('offer_min_stars')->default(0);
        $table->integer('craft_chance_permille')->default(0);
    }

    /**
     * Flat MessageMedia union column set — byte-compatible with the messages
     * domain tf_messages_media (both flatten the same 19-ctor union).
     */
    private static function messageMediaColumns(Blueprint $table): void
    {
        $table->string('constructor', 64);
        $table->boolean('spoiler')->default(false);
        $table->boolean('live_photo')->default(false);
        $table->boolean('nopremium')->default(false);
        $table->boolean('video')->default(false);
        $table->boolean('round')->default(false);
        $table->boolean('voice')->default(false);
        $table->boolean('force_large_media')->default(false);
        $table->boolean('force_small_media')->default(false);
        $table->boolean('manual')->default(false);
        $table->boolean('safe')->default(false);
        $table->boolean('shipping_address_requested')->default(false);
        $table->boolean('test')->default(false);
        $table->boolean('via_mention')->default(false);
        $table->boolean('only_new_subscribers')->default(false);
        $table->boolean('winners_are_visible')->default(false);
        $table->boolean('refunded')->default(false);
        $table->boolean('rtmp_stream')->default(false);
        $table->integer('ttl_seconds')->default(0);
        $table->integer('video_timestamp')->default(0);
        $table->text('phone_number')->default('');
        $table->text('first_name')->default('');
        $table->text('last_name')->default('');
        $table->text('vcard')->default('');
        $table->bigInteger('user_id')->default(0);
        $table->text('title')->default('');
        $table->text('address')->default('');
        $table->text('provider')->default('');
        $table->text('venue_id')->default('');
        $table->text('venue_type')->default('');
        $table->text('description')->default('');
        $table->integer('receipt_msg_id')->default(0);
        $table->text('currency')->default('');
        $table->bigInteger('total_amount')->default(0);
        $table->text('start_param')->default('');
        $table->integer('heading')->default(0);
        $table->integer('period')->default(0);
        $table->integer('proximity_notification_radius')->default(0);
        $table->integer('value')->default(0);
        $table->text('emoticon')->default('');
        $table->tinyInteger('peer_type')->default(0);
        $table->bigInteger('peer_id')->default(0);
        $table->bigInteger('channel_id')->default(0);
        $table->integer('additional_peers_count')->default(0);
        $table->integer('launch_msg_id')->default(0);
        $table->integer('winners_count')->default(0);
        $table->integer('unclaimed_count')->default(0);
        $table->text('prize_description')->default('');
        $table->integer('quantity')->default(0);
        $table->integer('months')->default(0);
        $table->bigInteger('stars')->default(0);
        $table->integer('until_date')->default(0);
        $table->bigInteger('stars_amount')->default(0);
    }
};
