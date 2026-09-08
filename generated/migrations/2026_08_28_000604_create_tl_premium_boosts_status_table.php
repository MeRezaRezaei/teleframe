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
        Schema::create('tl_premium_boosts_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_premium_boosts_status_boosts_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_premium_boosts_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('my_boost')->default(false);
            $table->integer('level');
            $table->integer('current_level_boosts');
            $table->integer('boosts');
            $table->integer('gift_boosts')->nullable();
            $table->integer('next_level_boosts')->nullable();
            $table->uuid('premium_audience')->nullable();
            $table->text('boost_url');
            $table->timestamps();
        });
        Schema::create('tl_premium_boosts_status_boosts_status__prepaid_giveaways', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_premium_boosts_status_boosts_status')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_500401dc2a5e35edae20');
        });
        Schema::create('tl_premium_boosts_status_boosts_status__my_boost_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_premium_boosts_status_boosts_status')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4bddbb6c1c5bae484f0e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_premium_boosts_status_boosts_status__my_boost_slots');
        Schema::dropIfExists('tl_premium_boosts_status_boosts_status__prepaid_giveaways');
        Schema::dropIfExists('tl_premium_boosts_status_boosts_status');
        Schema::dropIfExists('tl_premium_boosts_status');
    }
};
