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
        Schema::create('tf_bot_apps', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('access_hash')->unsigned();
        $table->text('short_name');
        $table->text('title');
        $table->text('description');
        $table->bigInteger('hash')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_photos', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('access_hash')->unsigned();
        $table->string('file_reference', 255);
        $table->integer('date')->unsigned();
        $table->integer('dc_id')->unsigned();
        $table->boolean('has_stickers')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_photos_sizes', function (Blueprint $table) {
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

        Schema::create('tf_photos_sizes_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_photos_video_sizes', function (Blueprint $table) {
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

        Schema::create('tf_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_bot_apps');

        Schema::dropIfExists('tf_photos');

        Schema::dropIfExists('tf_photos_sizes');

        Schema::dropIfExists('tf_photos_sizes_sizes');

        Schema::dropIfExists('tf_photos_video_sizes');

        Schema::dropIfExists('tf_photos_video_sizes_background_colors');

        Schema::dropIfExists('tf_photos_video_sizes_background_colors');

        Schema::dropIfExists('tf_photos_video_sizes');

        Schema::dropIfExists('tf_photos_sizes_sizes');

        Schema::dropIfExists('tf_photos_sizes');

        Schema::dropIfExists('tf_photos');

        Schema::dropIfExists('tf_bot_apps');

    }
};
