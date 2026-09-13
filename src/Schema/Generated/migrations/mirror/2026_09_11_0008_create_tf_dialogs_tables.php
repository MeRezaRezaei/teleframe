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
        Schema::create('tf_dialogs', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->integer('top_message')->unsigned();
        $table->integer('read_inbox_max_id')->unsigned();
        $table->integer('read_outbox_max_id')->unsigned();
        $table->integer('unread_count')->unsigned();
        $table->integer('unread_mentions_count')->unsigned();
        $table->integer('unread_reactions_count')->unsigned();
        $table->integer('unread_poll_votes_count')->unsigned();
        $table->boolean('pinned')->default(false);
        $table->boolean('unread_mark')->default(false);
        $table->boolean('view_forum_as_messages')->default(false);
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_notify_settings', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->boolean('show_previews')->default(false);
        $table->boolean('silent')->default(false);
        $table->integer('mute_until')->unsigned();
        $table->boolean('stories_muted')->default(false);
        $table->boolean('stories_hide_sender')->default(false);
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_notify_settings_ios_sound', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->text('title');
        $table->text('data');
        $table->bigInteger('id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_notify_settings_android_sound', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->text('title');
        $table->text('data');
        $table->bigInteger('id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_notify_settings_other_sound', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->text('title');
        $table->text('data');
        $table->bigInteger('id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_notify_settings_stories_ios_sound', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->text('title');
        $table->text('data');
        $table->bigInteger('id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_notify_settings_stories_android_sound', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->text('title');
        $table->text('data');
        $table->bigInteger('id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_notify_settings_stories_other_sound', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->text('title');
        $table->text('data');
        $table->bigInteger('id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_pts', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->integer('pts')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->integer('date')->unsigned();
        $table->boolean('no_webpage')->default(false);
        $table->boolean('invert_media')->default(false);
        $table->text('message');
        $table->bigInteger('effect')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
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
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_suggested_post', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->boolean('accepted')->default(false);
        $table->boolean('rejected')->default(false);
        $table->integer('schedule_date')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_suggested_post_price', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('amount')->unsigned();
        $table->integer('nanos')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_rich_message', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->boolean('rtl')->default(false);
        $table->boolean('part')->default(false);
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
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
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_text', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
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
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_author', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
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
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_items', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->boolean('checkbox')->default(false);
        $table->boolean('checked')->default(false);
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_items_text', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
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
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_caption', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
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
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
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
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_rows', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_rows_cells', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->boolean('header')->default(false);
        $table->boolean('align_center')->default(false);
        $table->boolean('align_right')->default(false);
        $table->boolean('valign_middle')->default(false);
        $table->boolean('valign_bottom')->default(false);
        $table->integer('colspan')->unsigned();
        $table->integer('rowspan')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_rows_cells_text', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
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
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_articles', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('url');
        $table->bigInteger('webpage_id')->unsigned();
        $table->text('title');
        $table->text('description');
        $table->bigInteger('photo_id')->unsigned();
        $table->text('author');
        $table->integer('published_date')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_blocks_geo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->text('constructor');
        $table->double('long');
        $table->double('lat');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('accuracy_radius')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_draft_rich_message_photos', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->bigInteger('id')->unsigned();
        $table->boolean('has_stickers')->default(false);
        $table->bigInteger('access_hash')->unsigned();
        $table->text('file_reference');
        $table->integer('date')->unsigned();
        $table->integer('dc_id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_photos_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('bytes');
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_photos_sizes_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_photos_video_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->double('video_start_ts');
        $table->bigInteger('emoji_id')->unsigned();
        $table->bigInteger('sticker_id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_documents', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('access_hash')->unsigned();
        $table->text('file_reference');
        $table->integer('date')->unsigned();
        $table->text('mime_type');
        $table->bigInteger('size')->unsigned();
        $table->integer('dc_id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_documents_thumbs', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('bytes');
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_documents_thumbs_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_documents_video_thumbs', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->double('video_start_ts');
        $table->bigInteger('emoji_id')->unsigned();
        $table->bigInteger('sticker_id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_documents_attributes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
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
        $table->primary(['account_id', 'peer_type', 'peer_id', 'position']);
        });

        Schema::create('tf_dialogs_draft_rich_message_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_folder_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->integer('folder_id')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_ttl_period', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->integer('ttl_period')->unsigned();
        $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_dialogs');

        Schema::dropIfExists('tf_dialogs_notify_settings');

        Schema::dropIfExists('tf_dialogs_notify_settings_ios_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_android_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_other_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_stories_ios_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_stories_android_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_stories_other_sound');

        Schema::dropIfExists('tf_dialogs_pts');

        Schema::dropIfExists('tf_dialogs_draft');

        Schema::dropIfExists('tf_dialogs_draft_entities');

        Schema::dropIfExists('tf_dialogs_draft_suggested_post');

        Schema::dropIfExists('tf_dialogs_draft_suggested_post_price');

        Schema::dropIfExists('tf_dialogs_draft_rich_message');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_text');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_author');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_items');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_items_text');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_caption');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_title');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_rows');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_rows_cells');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_rows_cells_text');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_articles');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_geo');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos_sizes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos_sizes_sizes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos_video_sizes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos_video_sizes_background_colors');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_thumbs');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_thumbs_sizes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_video_thumbs');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_attributes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_dialogs_folder_id');

        Schema::dropIfExists('tf_dialogs_ttl_period');

        Schema::dropIfExists('tf_dialogs_ttl_period');

        Schema::dropIfExists('tf_dialogs_folder_id');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_attributes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_video_thumbs');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_thumbs_sizes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents_thumbs');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_documents');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos_video_sizes_background_colors');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos_video_sizes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos_sizes_sizes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos_sizes');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_photos');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_geo');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_articles');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_rows_cells_text');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_rows_cells');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_rows');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_title');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_caption');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_items_text');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_items');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_author');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks_text');

        Schema::dropIfExists('tf_dialogs_draft_rich_message_blocks');

        Schema::dropIfExists('tf_dialogs_draft_rich_message');

        Schema::dropIfExists('tf_dialogs_draft_suggested_post_price');

        Schema::dropIfExists('tf_dialogs_draft_suggested_post');

        Schema::dropIfExists('tf_dialogs_draft_entities');

        Schema::dropIfExists('tf_dialogs_draft');

        Schema::dropIfExists('tf_dialogs_pts');

        Schema::dropIfExists('tf_dialogs_notify_settings_stories_other_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_stories_android_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_stories_ios_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_other_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_android_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings_ios_sound');

        Schema::dropIfExists('tf_dialogs_notify_settings');

        Schema::dropIfExists('tf_dialogs');

    }
};
