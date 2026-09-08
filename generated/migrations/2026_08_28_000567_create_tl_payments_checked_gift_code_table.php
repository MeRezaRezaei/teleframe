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
        Schema::create('tl_payments_checked_gift_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_checked_gift_code_checked_gift_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_checked_gift_code')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_giveaway')->default(false);
            $table->uuid('from_id')->nullable();
            $table->integer('giveaway_msg_id')->nullable();
            $table->bigInteger('to_id')->nullable();
            $table->integer('date');
            $table->integer('days');
            $table->integer('used_date')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_payments_checked_gift_code_checked_gift_code__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_checked_gift_code_checked_gift_code')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7f28bc681534f97ad1ea');
        });
        Schema::create('tl_payments_checked_gift_code_checked_gift_code__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_checked_gift_code_checked_gift_code')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c30302d6aeb60219fd31');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_checked_gift_code_checked_gift_code__users');
        Schema::dropIfExists('tl_payments_checked_gift_code_checked_gift_code__chats');
        Schema::dropIfExists('tl_payments_checked_gift_code_checked_gift_code');
        Schema::dropIfExists('tl_payments_checked_gift_code');
    }
};
