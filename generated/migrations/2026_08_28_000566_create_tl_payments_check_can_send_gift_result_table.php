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
        Schema::create('tl_payments_check_can_send_gift_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9d18767f4212bb6f9822ed42');
            $table->index('account_id', 'ix_59db812957ae3585f520e948');
        });
        Schema::create('tl_payments_check_can_send_gift_result_check__b2fde8ccbce4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_check_can_send_gift_result')->cascadeOnDelete();
            $table->uuid('reason');
            $table->index('reason', 'ix_9daed5b47d6e699825ce6870');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a1f21444e21d111bab1069b9');
        });
        Schema::create('tl_payments_check_can_send_gift_result_check__7028254cf06b', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_check_can_send_gift_result')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b36775ad00d2f22ce5c766c6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_check_can_send_gift_result_check__7028254cf06b');
        Schema::dropIfExists('tl_payments_check_can_send_gift_result_check__b2fde8ccbce4');
        Schema::dropIfExists('tl_payments_check_can_send_gift_result');
    }
};
