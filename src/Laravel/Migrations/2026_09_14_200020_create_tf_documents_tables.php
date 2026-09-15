<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->bigInteger('id');
            $table->bigInteger('access_hash');
            $table->text('file_reference');
            $table->integer('date');
            $table->text('mime_type');
            $table->bigInteger('size');
            $table->integer('dc_id');

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_documents_attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->string('constructor', 64)->default('');
            $table->integer('w')->default(0);
            $table->integer('h')->default(0);
            $table->double('duration')->default(0);
            $table->double('video_start_ts')->default(0);
            $table->integer('preload_prefix_size')->default(0);
            $table->string('video_codec', 255)->default('');
            $table->string('alt', 255)->default('');
            $table->string('file_name', 255)->default('');
            $table->string('title', 255)->default('');
            $table->string('performer', 255)->default('');
            $table->string('waveform', 255)->default('');
            $table->boolean('mask')->default(false);
            $table->boolean('round_message')->default(false);
            $table->boolean('supports_streaming')->default(false);
            $table->boolean('nosound')->default(false);
            $table->boolean('voice')->default(false);
            $table->boolean('free')->default(false);
            $table->boolean('text_color')->default(false);

            $table->primary(['account_id', 'id', 'position']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_documents')
                ->onDelete('cascade');
        });

        Schema::create('tf_documents_thumbs', function (Blueprint $table) {
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
                ->on('tf_documents')
                ->onDelete('cascade');
        });

        Schema::create('tf_documents_video_thumbs', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->string('constructor', 64)->default('');
            $table->string('type', 255)->default('');
            $table->integer('w')->default(0);
            $table->integer('h')->default(0);
            $table->integer('size')->default(0);
            $table->double('video_start_ts')->default(0);
            $table->bigInteger('emoji_id')->default(0);
            $table->bigInteger('sticker_id')->default(0);

            $table->primary(['account_id', 'id', 'position']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_documents')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_documents_video_thumbs');
        Schema::dropIfExists('tf_documents_thumbs');
        Schema::dropIfExists('tf_documents_attributes');
        Schema::dropIfExists('tf_documents');
    }
};
