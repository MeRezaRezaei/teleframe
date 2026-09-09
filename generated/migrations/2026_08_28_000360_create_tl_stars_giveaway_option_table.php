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
        Schema::create('tl_stars_giveaway_option', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a9a7c8e2541f6c76c50a7081');
            $table->index('account_id', 'ix_2223c5652b0e837145fdce45');
        });
        Schema::create('tl_stars_giveaway_option_stars_giveaway_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_giveaway_option')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('extended')->default(false);
            $table->boolean('tl_default')->default(false);
            $table->bigInteger('stars');
            $table->integer('yearly_boosts');
            $table->text('store_product')->nullable();
            $table->text('currency');
            $table->bigInteger('amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1c534df121bc6284e46fe654');
        });
        Schema::create('tl_stars_giveaway_option_stars_giveaway_option__winners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stars_giveaway_option_stars_giveaway_option')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5c8ff6fb0c05937be60e');
            $table->index('account_id', 'ix_1affe908159635e81ff3c5dd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_giveaway_option_stars_giveaway_option__winners');
        Schema::dropIfExists('tl_stars_giveaway_option_stars_giveaway_option');
        Schema::dropIfExists('tl_stars_giveaway_option');
    }
};
