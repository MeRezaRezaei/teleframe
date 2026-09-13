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
        Schema::create('tf_sticker_sets', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('access_hash')->unsigned();
        $table->text('title');
        $table->text('short_name');
        $table->integer('count')->unsigned();
        $table->integer('hash')->unsigned();
        $table->boolean('archived')->default(false);
        $table->boolean('official')->default(false);
        $table->boolean('masks')->default(false);
        $table->boolean('emojis')->default(false);
        $table->boolean('text_color')->default(false);
        $table->boolean('channel_emoji_status')->default(false);
        $table->boolean('creator')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_sticker_sets_installed_date', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('installed_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_sticker_sets_thumbs', function (Blueprint $table) {
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

        Schema::create('tf_sticker_sets_thumbs_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_sticker_sets_thumb_dc_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('thumb_dc_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_sticker_sets_thumb_version', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('thumb_version')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_sticker_sets_thumb_document_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('thumb_document_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_sticker_sets');

        Schema::dropIfExists('tf_sticker_sets_installed_date');

        Schema::dropIfExists('tf_sticker_sets_thumbs');

        Schema::dropIfExists('tf_sticker_sets_thumbs_sizes');

        Schema::dropIfExists('tf_sticker_sets_thumb_dc_id');

        Schema::dropIfExists('tf_sticker_sets_thumb_version');

        Schema::dropIfExists('tf_sticker_sets_thumb_document_id');

        Schema::dropIfExists('tf_sticker_sets_thumb_document_id');

        Schema::dropIfExists('tf_sticker_sets_thumb_version');

        Schema::dropIfExists('tf_sticker_sets_thumb_dc_id');

        Schema::dropIfExists('tf_sticker_sets_thumbs_sizes');

        Schema::dropIfExists('tf_sticker_sets_thumbs');

        Schema::dropIfExists('tf_sticker_sets_installed_date');

        Schema::dropIfExists('tf_sticker_sets');

    }
};
