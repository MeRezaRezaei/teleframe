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
        Schema::create('tf_dialog_filters', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->integer('id')->unsigned();
        $table->text('constructor');
        $table->boolean('contacts')->default(false);
        $table->boolean('non_contacts')->default(false);
        $table->boolean('groups')->default(false);
        $table->boolean('broadcasts')->default(false);
        $table->boolean('bots')->default(false);
        $table->boolean('exclude_muted')->default(false);
        $table->boolean('exclude_read')->default(false);
        $table->boolean('exclude_archived')->default(false);
        $table->boolean('title_noanimate')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_dialog_filters_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_dialog_filters_title_entities', function (Blueprint $table) {
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

        Schema::create('tf_dialog_filters_pinned_peers', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->bigInteger('chat_id')->unsigned();
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('access_hash')->unsigned();
        $table->bigInteger('channel_id')->unsigned();
        $table->integer('msg_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_dialog_filters_include_peers', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->bigInteger('chat_id')->unsigned();
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('access_hash')->unsigned();
        $table->bigInteger('channel_id')->unsigned();
        $table->integer('msg_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_dialog_filters_exclude_peers', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->bigInteger('chat_id')->unsigned();
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('access_hash')->unsigned();
        $table->bigInteger('channel_id')->unsigned();
        $table->integer('msg_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_dialog_filters_emoticon', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('emoticon');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_dialog_filters_color', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('color')->unsigned();
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_dialog_filters');

        Schema::dropIfExists('tf_dialog_filters_title');

        Schema::dropIfExists('tf_dialog_filters_title_entities');

        Schema::dropIfExists('tf_dialog_filters_pinned_peers');

        Schema::dropIfExists('tf_dialog_filters_include_peers');

        Schema::dropIfExists('tf_dialog_filters_exclude_peers');

        Schema::dropIfExists('tf_dialog_filters_emoticon');

        Schema::dropIfExists('tf_dialog_filters_color');

        Schema::dropIfExists('tf_dialog_filters_color');

        Schema::dropIfExists('tf_dialog_filters_emoticon');

        Schema::dropIfExists('tf_dialog_filters_exclude_peers');

        Schema::dropIfExists('tf_dialog_filters_include_peers');

        Schema::dropIfExists('tf_dialog_filters_pinned_peers');

        Schema::dropIfExists('tf_dialog_filters_title_entities');

        Schema::dropIfExists('tf_dialog_filters_title');

        Schema::dropIfExists('tf_dialog_filters');

    }
};
