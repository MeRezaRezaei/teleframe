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
        Schema::create('tl_sticker_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_317fc09fd498be60a0e2a372');
            $table->index('account_id', 'ix_1eb68fc14abe325e0d703795');
        });
        Schema::create('tl_sticker_set_sticker_set', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_set')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('archived')->default(false);
            $table->boolean('official')->default(false);
            $table->boolean('masks')->default(false);
            $table->boolean('emojis')->default(false);
            $table->boolean('text_color')->default(false);
            $table->boolean('channel_emoji_status')->default(false);
            $table->boolean('creator')->default(false);
            $table->integer('installed_date')->nullable();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->text('title');
            $table->text('short_name');
            $table->integer('thumb_dc_id')->nullable();
            $table->integer('thumb_version')->nullable();
            $table->bigInteger('thumb_document_id')->nullable();
            $table->index('thumb_document_id', 'ix_d06efeb8a3efe466a6f0982a');
            $table->integer('count');
            $table->integer('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7aaa47a0c55e6d266a121aba');
            $table->unique(['account_id', 'tl_id'], 'ux_a3b21ef95f17ed470e21');
        });
        Schema::create('tl_sticker_set_sticker_set__thumbs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_set_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a2b1bc04a08c021613f9');
            $table->index('account_id', 'ix_01414a03cbe2adeaf6eba900');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sticker_set_sticker_set__thumbs');
        Schema::dropIfExists('tl_sticker_set_sticker_set');
        Schema::dropIfExists('tl_sticker_set');
    }
};
