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
        Schema::create('tl_prepaid_giveaway', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f4c91f88b52aa2ec5374ae07');
            $table->index('account_id', 'ix_2d249faea8cfc269bc400c06');
        });
        Schema::create('tl_prepaid_giveaway_prepaid_giveaway', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_prepaid_giveaway')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->integer('months');
            $table->integer('quantity');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_29c8ccccc1e4f7a27838a2ae');
            $table->unique(['account_id', 'tl_id'], 'ux_a27eae291b0e9f0f3401');
        });
        Schema::create('tl_prepaid_giveaway_prepaid_stars_giveaway', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_prepaid_giveaway')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('stars');
            $table->integer('quantity');
            $table->integer('boosts');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bc1abb5308aea96bee25b624');
            $table->unique(['account_id', 'tl_id'], 'ux_bbd487bad6ff017a5b12');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_prepaid_giveaway_prepaid_stars_giveaway');
        Schema::dropIfExists('tl_prepaid_giveaway_prepaid_giveaway');
        Schema::dropIfExists('tl_prepaid_giveaway');
    }
};
