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
        Schema::create('tl_payments_star_gift_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_star_gift_collections_star_gift_collections', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_collections')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_payments_star_gift_collections_star_gift_c_3c2191db9981', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_collections_star_gift_collections')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_930a1cd8c51888c0d18b');
        });
        Schema::create('tl_payments_star_gift_collections_star_gift_c_72774827a67d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_collections')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_collections_star_gift_c_72774827a67d');
        Schema::dropIfExists('tl_payments_star_gift_collections_star_gift_c_3c2191db9981');
        Schema::dropIfExists('tl_payments_star_gift_collections_star_gift_collections');
        Schema::dropIfExists('tl_payments_star_gift_collections');
    }
};
