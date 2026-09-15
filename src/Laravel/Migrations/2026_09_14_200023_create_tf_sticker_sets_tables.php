<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_sticker_sets', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->bigInteger('id');
            $table->bigInteger('access_hash');
            $table->text('title');
            $table->text('short_name');
            $table->integer('count');
            $table->integer('hash');
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
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->string('constructor', 64)->default('');
            $table->integer('installed_date');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_sticker_sets')
                ->onDelete('cascade');
        });

        Schema::create('tf_sticker_sets_thumbs', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->string('constructor', 64)->default('');
            $table->string('type', 64);
            $table->integer('w')->default(0);
            $table->integer('h')->default(0);
            $table->integer('size')->default(0);
            $table->string('bytes', 2048)->default('');

            $table->primary(['account_id', 'id', 'position']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_sticker_sets')
                ->onDelete('cascade');
        });

        Schema::create('tf_sticker_sets_thumb_dc_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->string('constructor', 64)->default('');
            $table->integer('thumb_dc_id');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_sticker_sets')
                ->onDelete('cascade');
        });

        Schema::create('tf_sticker_sets_thumb_version', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->string('constructor', 64)->default('');
            $table->integer('thumb_version');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_sticker_sets')
                ->onDelete('cascade');
        });

        Schema::create('tf_sticker_sets_thumb_document_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->string('constructor', 64)->default('');
            $table->bigInteger('thumb_document_id');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_sticker_sets')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_sticker_sets_thumb_document_id');
        Schema::dropIfExists('tf_sticker_sets_thumb_version');
        Schema::dropIfExists('tf_sticker_sets_thumb_dc_id');
        Schema::dropIfExists('tf_sticker_sets_thumbs');
        Schema::dropIfExists('tf_sticker_sets_installed_date');
        Schema::dropIfExists('tf_sticker_sets');
    }
};
