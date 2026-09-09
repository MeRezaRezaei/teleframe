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
        Schema::create('tl_messages_all_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_468b7d2332e444aa36d43940');
            $table->index('account_id', 'ix_8c73447fe88c82c40133fdd1');
        });
        Schema::create('tl_messages_all_stickers_all_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_all_stickers')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e989d1c553be3a380d1e192b');
        });
        Schema::create('tl_messages_all_stickers_all_stickers__sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_all_stickers_all_stickers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_51ec8a33f928e47883db');
            $table->index('account_id', 'ix_1900add4468467d290a7b012');
        });
        Schema::create('tl_messages_all_stickers_all_stickers_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_all_stickers')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_de1f4fda12989e1a3f412af4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_all_stickers_all_stickers_not_modified');
        Schema::dropIfExists('tl_messages_all_stickers_all_stickers__sets');
        Schema::dropIfExists('tl_messages_all_stickers_all_stickers');
        Schema::dropIfExists('tl_messages_all_stickers');
    }
};
