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
        Schema::create('tl_payments_resale_star_gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_resale_star_gifts')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->text('next_offset')->nullable();
            $table->bigInteger('attributes_hash')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts__gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d8debd141496a4be9a0d');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gif_3aab68d26831', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0a7656cbc809918a5f49');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c2320b6473f2040c6d96');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts__counters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0be14209e581e46ff705');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5c0483336ab72aaad5b2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts__users');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts__counters');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts__chats');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gif_3aab68d26831');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts__gifts');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts');
        Schema::dropIfExists('tl_payments_resale_star_gifts');
    }
};
