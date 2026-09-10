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
        Schema::create('tl_sticker_set_sticker_set', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('archived')->default(false);
            $table->boolean('official')->default(false);
            $table->boolean('masks')->default(false);
            $table->boolean('emojis')->default(false);
            $table->boolean('text_color')->default(false);
            $table->boolean('channel_emoji_status')->default(false);
            $table->boolean('creator')->default(false);
            $table->integer('installed_date')->nullable();
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->text('title')->nullable();
            $table->text('short_name')->nullable();
            $table->integer('thumb_dc_id')->nullable();
            $table->integer('thumb_version')->nullable();
            $table->bigInteger('thumb_document_id')->nullable();
            $table->index('thumb_document_id', 'ix_d06efeb8a3efe466a6f0982a');
            $table->integer('count')->nullable();
            $table->integer('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c04ea2bd9b88074dcc6b56f3');
            $table->index('account_id', 'ix_7aaa47a0c55e6d266a121aba');
        });
        Schema::create('tl_sticker_set_sticker_set__thumbs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_b0e5a772acd9b10c36cb5be5')->references('id')->on('tl_sticker_set_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a2b1bc04a08c021613f9');
            $table->index('account_id', 'ix_01414a03cbe2adeaf6eba900');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sticker_set_sticker_set__thumbs');
        Schema::dropIfExists('tl_sticker_set_sticker_set');
    }
};
