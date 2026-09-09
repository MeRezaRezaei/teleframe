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
        Schema::create('tl_payments_unique_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c96b489aac0b9cd3e5665542');
            $table->index('account_id', 'ix_44413f4a3cc1841a480d8790');
        });
        Schema::create('tl_payments_unique_star_gift_unique_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_unique_star_gift')->cascadeOnDelete();
            $table->uuid('gift');
            $table->index('gift', 'ix_2be7621ec13a36d5b86499e5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c4a9ddbad01a7584afdfe19a');
        });
        Schema::create('tl_payments_unique_star_gift_unique_star_gift__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_unique_star_gift_unique_star_gift')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f549e291516938108305');
            $table->index('account_id', 'ix_e1f140b3fc3dde4fbc92a17e');
        });
        Schema::create('tl_payments_unique_star_gift_unique_star_gift__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_unique_star_gift_unique_star_gift')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_67136748af6c1cb5e875');
            $table->index('account_id', 'ix_cbd39210294e97a772310d3a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_unique_star_gift_unique_star_gift__users');
        Schema::dropIfExists('tl_payments_unique_star_gift_unique_star_gift__chats');
        Schema::dropIfExists('tl_payments_unique_star_gift_unique_star_gift');
        Schema::dropIfExists('tl_payments_unique_star_gift');
    }
};
