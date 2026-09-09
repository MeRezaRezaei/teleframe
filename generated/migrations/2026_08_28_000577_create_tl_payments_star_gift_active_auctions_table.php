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
            $table->index('constructor_id', 'ix_0b001d4a2c5f62b37ec2ca9a');
            $table->index('account_id', 'ix_9edd799f63db5a49778262dd');
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_803614be0a98', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_active_auctions')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_91c180f94bbdf568883be5d6');
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_ccb0d8ae92aa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_active_auctions_star_gi_803614be0a98')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_09a3eff11848acb7e489');
            $table->index('account_id', 'ix_f3fae79a320afbb04c45d67e');
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_3177461f7187', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_active_auctions_star_gi_803614be0a98')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f5542edfd356efae2cc9');
            $table->index('account_id', 'ix_d7b9d645912cc9f4176ae603');
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_410dcb298b8a', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_active_auctions_star_gi_803614be0a98')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e96df87ca7729be8d85b');
            $table->index('account_id', 'ix_71ff25c8d5da22cf591fa870');
        });
        Schema::create('tl_payments_star_gift_active_auctions_star_gi_1c32f1e9e4ab', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_active_auctions')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_782dfbb43c9a98585c1f4ed5');
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
