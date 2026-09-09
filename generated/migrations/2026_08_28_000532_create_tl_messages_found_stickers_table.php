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
        Schema::create('tl_messages_found_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3b111c533137b999c6ed033e');
            $table->index('account_id', 'ix_ea6e7b90dbb8d7c848fcf302');
        });
        Schema::create('tl_messages_found_stickers_found_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_found_stickers')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('next_offset')->nullable();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0263b4427186490d390d7ac0');
        });
        Schema::create('tl_messages_found_stickers_found_stickers__stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_found_stickers_found_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bfb7cd96b2914cc43a76');
            $table->index('account_id', 'ix_ad71abd0c796a3bac2752907');
        });
        Schema::create('tl_messages_found_stickers_found_stickers_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_found_stickers')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c3602220d8b92202795b33b9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_found_stickers_found_stickers_not_modified');
        Schema::dropIfExists('tl_messages_found_stickers_found_stickers__stickers');
        Schema::dropIfExists('tl_messages_found_stickers_found_stickers');
        Schema::dropIfExists('tl_messages_found_stickers');
    }
};
