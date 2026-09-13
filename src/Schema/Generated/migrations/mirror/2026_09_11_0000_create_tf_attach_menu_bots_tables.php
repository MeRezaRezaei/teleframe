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
        Schema::create('tf_attach_menu_bots', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_id')->unsigned();
        $table->text('short_name');
        $table->boolean('inactive')->default(false);
        $table->boolean('has_settings')->default(false);
        $table->boolean('request_write_access')->default(false);
        $table->boolean('show_in_attach_menu')->default(false);
        $table->boolean('show_in_side_menu')->default(false);
        $table->boolean('side_menu_disclaimer_needed')->default(false);
        $table->primary(['account_id', 'bot_id']);
        });

        Schema::create('tf_attach_menu_bots_icons', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('name');
        $table->primary(['account_id', 'bot_id', 'position']);
        });

        Schema::create('tf_documents', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('access_hash')->unsigned();
        $table->string('file_reference', 255);
        $table->integer('date')->unsigned();
        $table->text('mime_type');
        $table->bigInteger('size')->unsigned();
        $table->integer('dc_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_documents_attributes', function (Blueprint $table) {
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

        Schema::create('tf_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_documents_thumbs', function (Blueprint $table) {
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

        Schema::create('tf_documents_thumbs_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_documents_video_thumbs', function (Blueprint $table) {
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

        Schema::create('tf_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_attach_menu_bots_icons_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('name');
        $table->integer('color')->unsigned();
        $table->primary(['account_id', 'bot_id', 'position']);
        });

        Schema::create('tf_attach_menu_bots_peer_types', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('bot_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->primary(['account_id', 'bot_id', 'position']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_attach_menu_bots');

        Schema::dropIfExists('tf_attach_menu_bots_icons');

        Schema::dropIfExists('tf_documents');

        Schema::dropIfExists('tf_documents_attributes');

        Schema::dropIfExists('tf_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_documents_thumbs');

        Schema::dropIfExists('tf_documents_thumbs_sizes');

        Schema::dropIfExists('tf_documents_video_thumbs');

        Schema::dropIfExists('tf_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_attach_menu_bots_icons_colors');

        Schema::dropIfExists('tf_attach_menu_bots_peer_types');

        Schema::dropIfExists('tf_attach_menu_bots_peer_types');

        Schema::dropIfExists('tf_attach_menu_bots_icons_colors');

        Schema::dropIfExists('tf_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_documents_video_thumbs');

        Schema::dropIfExists('tf_documents_thumbs_sizes');

        Schema::dropIfExists('tf_documents_thumbs');

        Schema::dropIfExists('tf_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_documents_attributes');

        Schema::dropIfExists('tf_documents');

        Schema::dropIfExists('tf_attach_menu_bots_icons');

        Schema::dropIfExists('tf_attach_menu_bots');

    }
};
