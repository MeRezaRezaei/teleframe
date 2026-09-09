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
        Schema::create('tl_messages_faved_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0a71eb513d7cd67db41cfaf9');
            $table->index('account_id', 'ix_3cf04cd060526737aa3489e8');
        });
        Schema::create('tl_messages_faved_stickers_faved_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_faved_stickers')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b876986e58dd53141e189f2c');
        });
        Schema::create('tl_messages_faved_stickers_faved_stickers__packs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_faved_stickers_faved_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0171a8bb49753fb105e8');
            $table->index('account_id', 'ix_dee070d979ac7883697c7cee');
        });
        Schema::create('tl_messages_faved_stickers_faved_stickers__stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_faved_stickers_faved_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e0313552ead6208831ba');
            $table->index('account_id', 'ix_19e97c76893f658b32fe85ee');
        });
        Schema::create('tl_messages_faved_stickers_faved_stickers_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_faved_stickers')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4465d07f61e65d8a81c1029e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_faved_stickers_faved_stickers_not_modified');
        Schema::dropIfExists('tl_messages_faved_stickers_faved_stickers__stickers');
        Schema::dropIfExists('tl_messages_faved_stickers_faved_stickers__packs');
        Schema::dropIfExists('tl_messages_faved_stickers_faved_stickers');
        Schema::dropIfExists('tl_messages_faved_stickers');
    }
};
