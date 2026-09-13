<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_messages', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->integer('id')->unsigned();
        $table->text('constructor');
        $table->unsignedTinyInteger('peer_id_type');
        $table->bigInteger('peer_id_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->text('message');
        $table->boolean('out')->default(false);
        $table->boolean('mentioned')->default(false);
        $table->boolean('media_unread')->default(false);
        $table->boolean('silent')->default(false);
        $table->boolean('post')->default(false);
        $table->boolean('from_scheduled')->default(false);
        $table->boolean('legacy')->default(false);
        $table->boolean('edit_hide')->default(false);
        $table->boolean('pinned')->default(false);
        $table->boolean('noforwards')->default(false);
        $table->boolean('invert_media')->default(false);
        $table->boolean('offline')->default(false);
        $table->boolean('video_processing_pending')->default(false);
        $table->boolean('paid_suggested_post_stars')->default(false);
        $table->boolean('paid_suggested_post_ton')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_from_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('from_id_type');
        $table->bigInteger('from_id_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_from_boosts_applied', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('from_boosts_applied')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_from_rank', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('from_rank');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_saved_peer_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('saved_peer_id_type');
        $table->bigInteger('saved_peer_id_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_fwd_from', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('imported')->default(false);
        $table->boolean('saved_out')->default(false);
        $table->unsignedTinyInteger('from_id_type');
        $table->bigInteger('from_id_id')->unsigned();
        $table->text('from_name');
        $table->integer('date')->unsigned();
        $table->integer('channel_post')->unsigned();
        $table->text('post_author');
        $table->unsignedTinyInteger('saved_from_peer_type');
        $table->bigInteger('saved_from_peer_id')->unsigned();
        $table->integer('saved_from_msg_id')->unsigned();
        $table->unsignedTinyInteger('saved_from_id_type');
        $table->bigInteger('saved_from_id_id')->unsigned();
        $table->text('saved_from_name');
        $table->integer('saved_date')->unsigned();
        $table->text('psa_type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_via_bot_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('via_bot_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_via_business_bot_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('via_business_bot_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_guestchat_via_from', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('guestchat_via_from_type');
        $table->bigInteger('guestchat_via_from_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reply_to', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('reply_to_scheduled')->default(false);
        $table->boolean('forum_topic')->default(false);
        $table->boolean('quote')->default(false);
        $table->boolean('reply_to_ephemeral')->default(false);
        $table->integer('reply_to_msg_id')->unsigned();
        $table->unsignedTinyInteger('reply_to_peer_id_type');
        $table->bigInteger('reply_to_peer_id_id')->unsigned();
        $table->integer('reply_to_top_id')->unsigned();
        $table->text('quote_text');
        $table->integer('quote_offset')->unsigned();
        $table->integer('todo_item_id')->unsigned();
        $table->text('poll_option');
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->integer('story_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reply_to_reply_from', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('imported')->default(false);
        $table->boolean('saved_out')->default(false);
        $table->unsignedTinyInteger('from_id_type');
        $table->bigInteger('from_id_id')->unsigned();
        $table->text('from_name');
        $table->integer('date')->unsigned();
        $table->integer('channel_post')->unsigned();
        $table->text('post_author');
        $table->unsignedTinyInteger('saved_from_peer_type');
        $table->bigInteger('saved_from_peer_id')->unsigned();
        $table->integer('saved_from_msg_id')->unsigned();
        $table->unsignedTinyInteger('saved_from_id_type');
        $table->bigInteger('saved_from_id_id')->unsigned();
        $table->text('saved_from_name');
        $table->integer('saved_date')->unsigned();
        $table->text('psa_type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reply_to_quote_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('offset')->unsigned();
        $table->integer('length')->unsigned();
        $table->text('language');
        $table->text('url');
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('document_id')->unsigned();
        $table->boolean('collapsed')->default(false);
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->text('old_text');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_reply_markup', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('selective')->default(false);
        $table->boolean('single_use')->default(false);
        $table->text('placeholder');
        $table->boolean('resize')->default(false);
        $table->boolean('persistent')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reply_markup_rows', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_reply_markup_rows_buttons', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('text');
        $table->text('url');
        $table->boolean('requires_password')->default(false);
        $table->text('data');
        $table->boolean('same_peer')->default(false);
        $table->text('query');
        $table->text('fwd_text');
        $table->integer('button_id')->unsigned();
        $table->boolean('quiz')->default(false);
        $table->bigInteger('user_id')->unsigned();
        $table->integer('max_quantity')->unsigned();
        $table->text('copy_text');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_reply_markup_rows_buttons_style', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('bg_primary')->default(false);
        $table->boolean('bg_danger')->default(false);
        $table->boolean('bg_success')->default(false);
        $table->bigInteger('icon')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reply_markup_rows_buttons_peer_types', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_reply_markup_rows_buttons_peer_type', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('bot')->default(false);
        $table->boolean('premium')->default(false);
        $table->boolean('creator')->default(false);
        $table->boolean('bot_participant')->default(false);
        $table->boolean('has_username')->default(false);
        $table->boolean('forum')->default(false);
        $table->boolean('bot_managed')->default(false);
        $table->text('suggested_name');
        $table->text('suggested_username');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reply_markup_rows_buttons_peer_type_user_admin_rights', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('change_info')->default(false);
        $table->boolean('post_messages')->default(false);
        $table->boolean('edit_messages')->default(false);
        $table->boolean('delete_messages')->default(false);
        $table->boolean('ban_users')->default(false);
        $table->boolean('invite_users')->default(false);
        $table->boolean('pin_messages')->default(false);
        $table->boolean('add_admins')->default(false);
        $table->boolean('anonymous')->default(false);
        $table->boolean('manage_call')->default(false);
        $table->boolean('other')->default(false);
        $table->boolean('manage_topics')->default(false);
        $table->boolean('post_stories')->default(false);
        $table->boolean('edit_stories')->default(false);
        $table->boolean('delete_stories')->default(false);
        $table->boolean('manage_direct_messages')->default(false);
        $table->boolean('manage_ranks')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reply_markup_rows_buttons_peer_type_bot_admin_rights', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('change_info')->default(false);
        $table->boolean('post_messages')->default(false);
        $table->boolean('edit_messages')->default(false);
        $table->boolean('delete_messages')->default(false);
        $table->boolean('ban_users')->default(false);
        $table->boolean('invite_users')->default(false);
        $table->boolean('pin_messages')->default(false);
        $table->boolean('add_admins')->default(false);
        $table->boolean('anonymous')->default(false);
        $table->boolean('manage_call')->default(false);
        $table->boolean('other')->default(false);
        $table->boolean('manage_topics')->default(false);
        $table->boolean('post_stories')->default(false);
        $table->boolean('edit_stories')->default(false);
        $table->boolean('delete_stories')->default(false);
        $table->boolean('manage_direct_messages')->default(false);
        $table->boolean('manage_ranks')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('offset')->unsigned();
        $table->integer('length')->unsigned();
        $table->text('language');
        $table->text('url');
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('document_id')->unsigned();
        $table->boolean('collapsed')->default(false);
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->text('old_text');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_views', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('views')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_forwards', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('forwards')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_replies', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('comments')->default(false);
        $table->integer('replies')->unsigned();
        $table->integer('replies_pts')->unsigned();
        $table->unsignedTinyInteger('recent_repliers_type');
        $table->bigInteger('recent_repliers_id')->unsigned();
        $table->bigInteger('channel_id')->unsigned();
        $table->integer('max_id')->unsigned();
        $table->integer('read_max_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_edit_date', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('edit_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_post_author', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('post_author');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_grouped_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('grouped_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reactions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('min')->default(false);
        $table->boolean('can_see_list')->default(false);
        $table->boolean('reactions_as_tags')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reactions_results', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('chosen_order')->unsigned();
        $table->integer('count')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_reactions_recent_reactions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->boolean('big')->default(false);
        $table->boolean('unread')->default(false);
        $table->boolean('my')->default(false);
        $table->unsignedTinyInteger('peer_id_type');
        $table->bigInteger('peer_id_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_reactions_top_reactors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->boolean('top')->default(false);
        $table->boolean('my')->default(false);
        $table->boolean('anonymous')->default(false);
        $table->unsignedTinyInteger('peer_id_type');
        $table->bigInteger('peer_id_id')->unsigned();
        $table->integer('count')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_restriction_reason', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('platform');
        $table->text('reason');
        $table->text('text');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_ttl_period', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('ttl_period')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_quick_reply_shortcut_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('quick_reply_shortcut_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_effect', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('effect')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_factcheck', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('need_check')->default(false);
        $table->text('country');
        $table->bigInteger('hash')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_factcheck_text', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_factcheck_text_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('offset')->unsigned();
        $table->integer('length')->unsigned();
        $table->text('language');
        $table->text('url');
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('document_id')->unsigned();
        $table->boolean('collapsed')->default(false);
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->text('old_text');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_report_delivery_until_date', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('report_delivery_until_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_paid_message_stars', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('paid_message_stars')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_suggested_post', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('accepted')->default(false);
        $table->boolean('rejected')->default(false);
        $table->integer('schedule_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_suggested_post_price', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('amount')->unsigned();
        $table->integer('nanos')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_schedule_repeat_period', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('schedule_repeat_period')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_summary_from_language', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('summary_from_language');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_rich_message', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('rtl')->default(false);
        $table->boolean('part')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_rich_message_blocks', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('published_date')->unsigned();
        $table->text('language');
        $table->text('name');
        $table->boolean('spoiler')->default(false);
        $table->bigInteger('photo_id')->unsigned();
        $table->text('url');
        $table->bigInteger('webpage_id')->unsigned();
        $table->boolean('autoplay')->default(false);
        $table->boolean('loop')->default(false);
        $table->bigInteger('video_id')->unsigned();
        $table->boolean('full_width')->default(false);
        $table->boolean('allow_scrolling')->default(false);
        $table->text('html');
        $table->bigInteger('poster_photo_id')->unsigned();
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->bigInteger('author_photo_id')->unsigned();
        $table->text('author');
        $table->integer('date')->unsigned();
        $table->bigInteger('audio_id')->unsigned();
        $table->boolean('bordered')->default(false);
        $table->boolean('striped')->default(false);
        $table->boolean('reversed')->default(false);
        $table->integer('start')->unsigned();
        $table->text('type');
        $table->boolean('open')->default(false);
        $table->integer('zoom')->unsigned();
        $table->text('source');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_blocks_text', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('text');
        $table->text('url');
        $table->bigInteger('webpage_id')->unsigned();
        $table->text('email');
        $table->text('phone');
        $table->bigInteger('document_id')->unsigned();
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->text('name');
        $table->text('source');
        $table->text('alt');
        $table->bigInteger('user_id')->unsigned();
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_rich_message_blocks_author', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('text');
        $table->text('url');
        $table->bigInteger('webpage_id')->unsigned();
        $table->text('email');
        $table->text('phone');
        $table->bigInteger('document_id')->unsigned();
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->text('name');
        $table->text('source');
        $table->text('alt');
        $table->bigInteger('user_id')->unsigned();
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_rich_message_blocks_items', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->boolean('checkbox')->default(false);
        $table->boolean('checked')->default(false);
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_blocks_items_text', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('text');
        $table->text('url');
        $table->bigInteger('webpage_id')->unsigned();
        $table->text('email');
        $table->text('phone');
        $table->bigInteger('document_id')->unsigned();
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->text('name');
        $table->text('source');
        $table->text('alt');
        $table->bigInteger('user_id')->unsigned();
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_rich_message_blocks_caption', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('text');
        $table->text('url');
        $table->bigInteger('webpage_id')->unsigned();
        $table->text('email');
        $table->text('phone');
        $table->bigInteger('document_id')->unsigned();
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->text('name');
        $table->text('source');
        $table->text('alt');
        $table->bigInteger('user_id')->unsigned();
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_rich_message_blocks_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('text');
        $table->text('url');
        $table->bigInteger('webpage_id')->unsigned();
        $table->text('email');
        $table->text('phone');
        $table->bigInteger('document_id')->unsigned();
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->text('name');
        $table->text('source');
        $table->text('alt');
        $table->bigInteger('user_id')->unsigned();
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_rich_message_blocks_rows', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_blocks_rows_cells', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->boolean('header')->default(false);
        $table->boolean('align_center')->default(false);
        $table->boolean('align_right')->default(false);
        $table->boolean('valign_middle')->default(false);
        $table->boolean('valign_bottom')->default(false);
        $table->integer('colspan')->unsigned();
        $table->integer('rowspan')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_blocks_rows_cells_text', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('text');
        $table->text('url');
        $table->bigInteger('webpage_id')->unsigned();
        $table->text('email');
        $table->text('phone');
        $table->bigInteger('document_id')->unsigned();
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->text('name');
        $table->text('source');
        $table->text('alt');
        $table->bigInteger('user_id')->unsigned();
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_rich_message_blocks_articles', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('url');
        $table->bigInteger('webpage_id')->unsigned();
        $table->text('title');
        $table->text('description');
        $table->bigInteger('photo_id')->unsigned();
        $table->text('author');
        $table->integer('published_date')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_blocks_geo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->double('long');
        $table->double('lat');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('accuracy_radius')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_rich_message_photos', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->boolean('has_stickers')->default(false);
        $table->bigInteger('access_hash')->unsigned();
        $table->text('file_reference');
        $table->integer('date')->unsigned();
        $table->integer('dc_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_photos_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('bytes');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_photos_sizes_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_photos_video_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->double('video_start_ts');
        $table->bigInteger('emoji_id')->unsigned();
        $table->bigInteger('sticker_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_documents', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->bigInteger('access_hash')->unsigned();
        $table->text('file_reference');
        $table->integer('date')->unsigned();
        $table->text('mime_type');
        $table->bigInteger('size')->unsigned();
        $table->integer('dc_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_documents_thumbs', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('bytes');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_documents_thumbs_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_documents_video_thumbs', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->double('video_start_ts');
        $table->bigInteger('emoji_id')->unsigned();
        $table->bigInteger('sticker_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_documents_attributes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->boolean('mask')->default(false);
        $table->text('alt');
        $table->boolean('round_message')->default(false);
        $table->boolean('supports_streaming')->default(false);
        $table->boolean('nosound')->default(false);
        $table->double('duration');
        $table->integer('preload_prefix_size')->unsigned();
        $table->double('video_start_ts');
        $table->text('video_codec');
        $table->boolean('voice')->default(false);
        $table->text('title');
        $table->text('performer');
        $table->text('waveform');
        $table->text('file_name');
        $table->boolean('free')->default(false);
        $table->boolean('text_color')->default(false);
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_rich_message_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_messages');

        Schema::dropIfExists('tf_messages_from_id');

        Schema::dropIfExists('tf_messages_from_boosts_applied');

        Schema::dropIfExists('tf_messages_from_rank');

        Schema::dropIfExists('tf_messages_saved_peer_id');

        Schema::dropIfExists('tf_messages_fwd_from');

        Schema::dropIfExists('tf_messages_via_bot_id');

        Schema::dropIfExists('tf_messages_via_business_bot_id');

        Schema::dropIfExists('tf_messages_guestchat_via_from');

        Schema::dropIfExists('tf_messages_reply_to');

        Schema::dropIfExists('tf_messages_reply_to_reply_from');

        Schema::dropIfExists('tf_messages_reply_to_quote_entities');

        Schema::dropIfExists('tf_messages_reply_markup');

        Schema::dropIfExists('tf_messages_reply_markup_rows');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_style');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_peer_types');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_peer_type');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_peer_type_user_admin_rights');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_peer_type_bot_admin_rights');

        Schema::dropIfExists('tf_messages_entities');

        Schema::dropIfExists('tf_messages_views');

        Schema::dropIfExists('tf_messages_forwards');

        Schema::dropIfExists('tf_messages_replies');

        Schema::dropIfExists('tf_messages_edit_date');

        Schema::dropIfExists('tf_messages_post_author');

        Schema::dropIfExists('tf_messages_grouped_id');

        Schema::dropIfExists('tf_messages_reactions');

        Schema::dropIfExists('tf_messages_reactions_results');

        Schema::dropIfExists('tf_messages_reactions_recent_reactions');

        Schema::dropIfExists('tf_messages_reactions_top_reactors');

        Schema::dropIfExists('tf_messages_restriction_reason');

        Schema::dropIfExists('tf_messages_ttl_period');

        Schema::dropIfExists('tf_messages_quick_reply_shortcut_id');

        Schema::dropIfExists('tf_messages_effect');

        Schema::dropIfExists('tf_messages_factcheck');

        Schema::dropIfExists('tf_messages_factcheck_text');

        Schema::dropIfExists('tf_messages_factcheck_text_entities');

        Schema::dropIfExists('tf_messages_report_delivery_until_date');

        Schema::dropIfExists('tf_messages_paid_message_stars');

        Schema::dropIfExists('tf_messages_suggested_post');

        Schema::dropIfExists('tf_messages_suggested_post_price');

        Schema::dropIfExists('tf_messages_schedule_repeat_period');

        Schema::dropIfExists('tf_messages_summary_from_language');

        Schema::dropIfExists('tf_messages_rich_message');

        Schema::dropIfExists('tf_messages_rich_message_blocks');

        Schema::dropIfExists('tf_messages_rich_message_blocks_text');

        Schema::dropIfExists('tf_messages_rich_message_blocks_author');

        Schema::dropIfExists('tf_messages_rich_message_blocks_items');

        Schema::dropIfExists('tf_messages_rich_message_blocks_items_text');

        Schema::dropIfExists('tf_messages_rich_message_blocks_caption');

        Schema::dropIfExists('tf_messages_rich_message_blocks_title');

        Schema::dropIfExists('tf_messages_rich_message_blocks_rows');

        Schema::dropIfExists('tf_messages_rich_message_blocks_rows_cells');

        Schema::dropIfExists('tf_messages_rich_message_blocks_rows_cells_text');

        Schema::dropIfExists('tf_messages_rich_message_blocks_articles');

        Schema::dropIfExists('tf_messages_rich_message_blocks_geo');

        Schema::dropIfExists('tf_messages_rich_message_photos');

        Schema::dropIfExists('tf_messages_rich_message_photos_sizes');

        Schema::dropIfExists('tf_messages_rich_message_photos_sizes_sizes');

        Schema::dropIfExists('tf_messages_rich_message_photos_video_sizes');

        Schema::dropIfExists('tf_messages_rich_message_photos_video_sizes_background_colors');

        Schema::dropIfExists('tf_messages_rich_message_documents');

        Schema::dropIfExists('tf_messages_rich_message_documents_thumbs');

        Schema::dropIfExists('tf_messages_rich_message_documents_thumbs_sizes');

        Schema::dropIfExists('tf_messages_rich_message_documents_video_thumbs');

        Schema::dropIfExists('tf_messages_rich_message_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_messages_rich_message_documents_attributes');

        Schema::dropIfExists('tf_messages_rich_message_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_messages_rich_message_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_messages_rich_message_documents_attributes');

        Schema::dropIfExists('tf_messages_rich_message_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_messages_rich_message_documents_video_thumbs');

        Schema::dropIfExists('tf_messages_rich_message_documents_thumbs_sizes');

        Schema::dropIfExists('tf_messages_rich_message_documents_thumbs');

        Schema::dropIfExists('tf_messages_rich_message_documents');

        Schema::dropIfExists('tf_messages_rich_message_photos_video_sizes_background_colors');

        Schema::dropIfExists('tf_messages_rich_message_photos_video_sizes');

        Schema::dropIfExists('tf_messages_rich_message_photos_sizes_sizes');

        Schema::dropIfExists('tf_messages_rich_message_photos_sizes');

        Schema::dropIfExists('tf_messages_rich_message_photos');

        Schema::dropIfExists('tf_messages_rich_message_blocks_geo');

        Schema::dropIfExists('tf_messages_rich_message_blocks_articles');

        Schema::dropIfExists('tf_messages_rich_message_blocks_rows_cells_text');

        Schema::dropIfExists('tf_messages_rich_message_blocks_rows_cells');

        Schema::dropIfExists('tf_messages_rich_message_blocks_rows');

        Schema::dropIfExists('tf_messages_rich_message_blocks_title');

        Schema::dropIfExists('tf_messages_rich_message_blocks_caption');

        Schema::dropIfExists('tf_messages_rich_message_blocks_items_text');

        Schema::dropIfExists('tf_messages_rich_message_blocks_items');

        Schema::dropIfExists('tf_messages_rich_message_blocks_author');

        Schema::dropIfExists('tf_messages_rich_message_blocks_text');

        Schema::dropIfExists('tf_messages_rich_message_blocks');

        Schema::dropIfExists('tf_messages_rich_message');

        Schema::dropIfExists('tf_messages_summary_from_language');

        Schema::dropIfExists('tf_messages_schedule_repeat_period');

        Schema::dropIfExists('tf_messages_suggested_post_price');

        Schema::dropIfExists('tf_messages_suggested_post');

        Schema::dropIfExists('tf_messages_paid_message_stars');

        Schema::dropIfExists('tf_messages_report_delivery_until_date');

        Schema::dropIfExists('tf_messages_factcheck_text_entities');

        Schema::dropIfExists('tf_messages_factcheck_text');

        Schema::dropIfExists('tf_messages_factcheck');

        Schema::dropIfExists('tf_messages_effect');

        Schema::dropIfExists('tf_messages_quick_reply_shortcut_id');

        Schema::dropIfExists('tf_messages_ttl_period');

        Schema::dropIfExists('tf_messages_restriction_reason');

        Schema::dropIfExists('tf_messages_reactions_top_reactors');

        Schema::dropIfExists('tf_messages_reactions_recent_reactions');

        Schema::dropIfExists('tf_messages_reactions_results');

        Schema::dropIfExists('tf_messages_reactions');

        Schema::dropIfExists('tf_messages_grouped_id');

        Schema::dropIfExists('tf_messages_post_author');

        Schema::dropIfExists('tf_messages_edit_date');

        Schema::dropIfExists('tf_messages_replies');

        Schema::dropIfExists('tf_messages_forwards');

        Schema::dropIfExists('tf_messages_views');

        Schema::dropIfExists('tf_messages_entities');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_peer_type_bot_admin_rights');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_peer_type_user_admin_rights');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_peer_type');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_peer_types');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons_style');

        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons');

        Schema::dropIfExists('tf_messages_reply_markup_rows');

        Schema::dropIfExists('tf_messages_reply_markup');

        Schema::dropIfExists('tf_messages_reply_to_quote_entities');

        Schema::dropIfExists('tf_messages_reply_to_reply_from');

        Schema::dropIfExists('tf_messages_reply_to');

        Schema::dropIfExists('tf_messages_guestchat_via_from');

        Schema::dropIfExists('tf_messages_via_business_bot_id');

        Schema::dropIfExists('tf_messages_via_bot_id');

        Schema::dropIfExists('tf_messages_fwd_from');

        Schema::dropIfExists('tf_messages_saved_peer_id');

        Schema::dropIfExists('tf_messages_from_rank');

        Schema::dropIfExists('tf_messages_from_boosts_applied');

        Schema::dropIfExists('tf_messages_from_id');

        Schema::dropIfExists('tf_messages');

    }
};
