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
        Schema::create('tl_payments_check_can_send_gift_result_check__b2fde8ccbce4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('reason')->nullable();
            $table->index('reason', 'ix_9daed5b47d6e699825ce6870');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_aeb2e3944593f289a36d51cb');
            $table->index('account_id', 'ix_a1f21444e21d111bab1069b9');
        });
        Schema::create('tl_payments_check_can_send_gift_result_check__7028254cf06b', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f8d3ff37a0ddbfea071c027b');
            $table->index('account_id', 'ix_b36775ad00d2f22ce5c766c6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_check_can_send_gift_result_check__7028254cf06b');
        Schema::dropIfExists('tl_payments_check_can_send_gift_result_check__b2fde8ccbce4');
    }
};
