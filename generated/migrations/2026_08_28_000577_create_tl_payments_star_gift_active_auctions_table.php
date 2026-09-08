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
        Schema::create('tl_payments_star_gift_active_auctions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_803614be0a98', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_active_auctions')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_ccb0d8ae92aa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_active_auctions_star_gi_803614be0a98')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_09a3eff11848acb7e489');
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_3177461f7187', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_active_auctions_star_gi_803614be0a98')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f5542edfd356efae2cc9');
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_410dcb298b8a', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_active_auctions_star_gi_803614be0a98')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e96df87ca7729be8d85b');
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_1c32f1e9e4ab', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_active_auctions')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_active_auctions_star_gi_1c32f1e9e4ab');
        Schema::dropIfExists('tl_payments_star_gift_active_auctions_star_gi_410dcb298b8a');
        Schema::dropIfExists('tl_payments_star_gift_active_auctions_star_gi_3177461f7187');
        Schema::dropIfExists('tl_payments_star_gift_active_auctions_star_gi_ccb0d8ae92aa');
        Schema::dropIfExists('tl_payments_star_gift_active_auctions_star_gi_803614be0a98');
        Schema::dropIfExists('tl_payments_star_gift_active_auctions');
    }
};
