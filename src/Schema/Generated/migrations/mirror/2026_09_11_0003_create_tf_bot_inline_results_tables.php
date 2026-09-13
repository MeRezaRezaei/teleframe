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
        Schema::create('tf_bot_inline_results', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->text('id');
        $table->text('constructor');
        $table->text('type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_send_message', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('invert_media')->default(false);
        $table->text('message');
        $table->boolean('no_webpage')->default(false);
        $table->integer('heading')->unsigned();
        $table->integer('period')->unsigned();
        $table->integer('proximity_notification_radius')->unsigned();
        $table->text('title');
        $table->text('address');
        $table->text('provider');
        $table->text('venue_id');
        $table->text('venue_type');
        $table->text('phone_number');
        $table->text('first_name');
        $table->text('last_name');
        $table->text('vcard');
        $table->boolean('shipping_address_requested')->default(false);
        $table->boolean('test')->default(false);
        $table->text('description');
        $table->text('currency');
        $table->bigInteger('total_amount')->unsigned();
        $table->boolean('force_large_media')->default(false);
        $table->boolean('force_small_media')->default(false);
        $table->boolean('manual')->default(false);
        $table->boolean('safe')->default(false);
        $table->text('url');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_send_message_entities', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_reply_markup', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_reply_markup_rows', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_bot_inline_results_send_message_reply_markup_rows_buttons', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_reply_markup_rows_buttons_style', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('bg_primary')->default(false);
        $table->boolean('bg_danger')->default(false);
        $table->boolean('bg_success')->default(false);
        $table->bigInteger('icon')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_types', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_user_admin_rights', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_bot_admin_rights', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_geo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->double('long');
        $table->double('lat');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('accuracy_radius')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_send_message_photo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('url');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('mime_type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_send_message_photo_attributes', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('rtl')->default(false);
        $table->boolean('part')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_text', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_author', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_items', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->boolean('checkbox')->default(false);
        $table->boolean('checked')->default(false);
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_items_text', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_caption', function (Blueprint $table) {
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

        Schema::create('tf_chats', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('title');
        $table->integer('participants_count')->unsigned();
        $table->integer('date')->unsigned();
        $table->integer('version')->unsigned();
        $table->boolean('creator')->default(false);
        $table->boolean('left')->default(false);
        $table->boolean('deactivated')->default(false);
        $table->boolean('call_active')->default(false);
        $table->boolean('call_not_empty')->default(false);
        $table->boolean('noforwards')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_chats_photo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('has_video')->default(false);
        $table->bigInteger('photo_id')->unsigned();
        $table->text('stripped_thumb');
        $table->integer('dc_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_chats_migrated_to', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('channel_id')->unsigned();
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('msg_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_chats_admin_rights', function (Blueprint $table) {
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

        Schema::create('tf_chats_default_banned_rights', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('view_messages')->default(false);
        $table->boolean('send_messages')->default(false);
        $table->boolean('send_media')->default(false);
        $table->boolean('send_stickers')->default(false);
        $table->boolean('send_gifs')->default(false);
        $table->boolean('send_games')->default(false);
        $table->boolean('send_inline')->default(false);
        $table->boolean('embed_links')->default(false);
        $table->boolean('send_polls')->default(false);
        $table->boolean('change_info')->default(false);
        $table->boolean('invite_users')->default(false);
        $table->boolean('pin_messages')->default(false);
        $table->boolean('manage_topics')->default(false);
        $table->boolean('send_photos')->default(false);
        $table->boolean('send_videos')->default(false);
        $table->boolean('send_roundvideos')->default(false);
        $table->boolean('send_audios')->default(false);
        $table->boolean('send_voices')->default(false);
        $table->boolean('send_docs')->default(false);
        $table->boolean('send_plain')->default(false);
        $table->boolean('edit_rank')->default(false);
        $table->boolean('send_reactions')->default(false);
        $table->integer('until_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_title', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_rows', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells_text', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_articles', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_blocks_geo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->double('long');
        $table->double('lat');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('accuracy_radius')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message_photos', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_photos_sizes', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_photos_sizes_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message_photos_video_sizes', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message_documents', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_documents_thumbs', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_documents_thumbs_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_bot_inline_results_send_message_rich_message_documents_attributes', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_send_message_rich_message_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('title');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_description', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('description');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_url', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('url');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_thumb', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('url');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('mime_type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_thumb_attributes', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_thumb_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_content', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('url');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('mime_type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_content_attributes', function (Blueprint $table) {
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

        Schema::create('tf_bot_inline_results_content_attributes_mask_coords', function (Blueprint $table) {
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
        Schema::dropIfExists('tf_bot_inline_results');

        Schema::dropIfExists('tf_bot_inline_results_send_message');

        Schema::dropIfExists('tf_bot_inline_results_send_message_entities');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_style');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_types');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_user_admin_rights');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_bot_admin_rights');

        Schema::dropIfExists('tf_bot_inline_results_send_message_geo');

        Schema::dropIfExists('tf_bot_inline_results_send_message_photo');

        Schema::dropIfExists('tf_bot_inline_results_send_message_photo_attributes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_photo_attributes_mask_coords');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_text');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_author');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_items');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_items_text');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_caption');

        Schema::dropIfExists('tf_chats');

        Schema::dropIfExists('tf_chats_photo');

        Schema::dropIfExists('tf_chats_migrated_to');

        Schema::dropIfExists('tf_chats_admin_rights');

        Schema::dropIfExists('tf_chats_default_banned_rights');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_title');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_rows');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells_text');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_articles');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_geo');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos_sizes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos_sizes_sizes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos_video_sizes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos_video_sizes_background_colors');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_thumbs');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_thumbs_sizes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_attributes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_bot_inline_results_title');

        Schema::dropIfExists('tf_bot_inline_results_description');

        Schema::dropIfExists('tf_bot_inline_results_url');

        Schema::dropIfExists('tf_bot_inline_results_thumb');

        Schema::dropIfExists('tf_bot_inline_results_thumb_attributes');

        Schema::dropIfExists('tf_bot_inline_results_thumb_attributes_mask_coords');

        Schema::dropIfExists('tf_bot_inline_results_content');

        Schema::dropIfExists('tf_bot_inline_results_content_attributes');

        Schema::dropIfExists('tf_bot_inline_results_content_attributes_mask_coords');

        Schema::dropIfExists('tf_bot_inline_results_content_attributes_mask_coords');

        Schema::dropIfExists('tf_bot_inline_results_content_attributes');

        Schema::dropIfExists('tf_bot_inline_results_content');

        Schema::dropIfExists('tf_bot_inline_results_thumb_attributes_mask_coords');

        Schema::dropIfExists('tf_bot_inline_results_thumb_attributes');

        Schema::dropIfExists('tf_bot_inline_results_thumb');

        Schema::dropIfExists('tf_bot_inline_results_url');

        Schema::dropIfExists('tf_bot_inline_results_description');

        Schema::dropIfExists('tf_bot_inline_results_title');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_attributes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_thumbs_sizes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents_thumbs');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_documents');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos_video_sizes_background_colors');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos_video_sizes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos_sizes_sizes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos_sizes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_photos');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_geo');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_articles');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells_text');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_rows');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_title');

        Schema::dropIfExists('tf_chats_default_banned_rights');

        Schema::dropIfExists('tf_chats_admin_rights');

        Schema::dropIfExists('tf_chats_migrated_to');

        Schema::dropIfExists('tf_chats_photo');

        Schema::dropIfExists('tf_chats');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_caption');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_items_text');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_items');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_author');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks_text');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message_blocks');

        Schema::dropIfExists('tf_bot_inline_results_send_message_rich_message');

        Schema::dropIfExists('tf_bot_inline_results_send_message_photo_attributes_mask_coords');

        Schema::dropIfExists('tf_bot_inline_results_send_message_photo_attributes');

        Schema::dropIfExists('tf_bot_inline_results_send_message_photo');

        Schema::dropIfExists('tf_bot_inline_results_send_message_geo');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_bot_admin_rights');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_user_admin_rights');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_types');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons_style');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows_buttons');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup_rows');

        Schema::dropIfExists('tf_bot_inline_results_send_message_reply_markup');

        Schema::dropIfExists('tf_bot_inline_results_send_message_entities');

        Schema::dropIfExists('tf_bot_inline_results_send_message');

        Schema::dropIfExists('tf_bot_inline_results');

    }
};
