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
        Schema::create('tl_bot_app', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_67d50113820c416dfb18c6ea');
            $table->index('account_id', 'ix_bcfcd86faf696af41fa79145');
        });
        Schema::create('tl_bot_app_bot_app', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_app')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->text('short_name');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo');
            $table->index('photo', 'ix_f6ce3aaaf9d77a145ca2fc7d');
            $table->uuid('document')->nullable();
            $table->index('document', 'ix_05633c5c0ba5052d3467bc8e');
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b60d48f37e646b0c6fd085c4');
            $table->unique(['account_id', 'tl_id'], 'ux_1f9fc5d9dd2a9e14e6a3');
        });
        Schema::create('tl_bot_app_bot_app_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_app')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_92459e5838a986335501104b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_app_bot_app_not_modified');
        Schema::dropIfExists('tl_bot_app_bot_app');
        Schema::dropIfExists('tl_bot_app');
    }
};
