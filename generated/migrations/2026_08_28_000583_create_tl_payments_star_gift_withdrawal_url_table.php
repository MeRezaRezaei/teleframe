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
        Schema::create('tl_payments_star_gift_withdrawal_url', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f5106821a3d401f6d54e43ab');
            $table->index('account_id', 'ix_04e2b2548bbd01dc8385199a');
        });
        Schema::create('tl_payments_star_gift_withdrawal_url_star_gif_98844d3a363c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_withdrawal_url')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b5a5768df733888c27bf0288');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_withdrawal_url_star_gif_98844d3a363c');
        Schema::dropIfExists('tl_payments_star_gift_withdrawal_url');
    }
};
