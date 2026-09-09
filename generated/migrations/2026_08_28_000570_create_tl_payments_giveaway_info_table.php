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
        Schema::create('tl_payments_giveaway_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3ff1ff6f3e23b09027656081');
            $table->index('account_id', 'ix_1e1f348ab68c141e006c6e70');
        });
        Schema::create('tl_payments_giveaway_info_giveaway_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_giveaway_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('participating')->default(false);
            $table->boolean('preparing_results')->default(false);
            $table->integer('start_date');
            $table->integer('joined_too_early_date')->nullable();
            $table->bigInteger('admin_disallowed_chat_id')->nullable();
            $table->index('admin_disallowed_chat_id', 'ix_70ab9d923d2aecf8439736b7');
            $table->text('disallowed_country')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e50a38b37539bbe097fca912');
        });
        Schema::create('tl_payments_giveaway_info_giveaway_info_results', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_giveaway_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('winner')->default(false);
            $table->boolean('refunded')->default(false);
            $table->integer('start_date');
            $table->text('gift_code_slug')->nullable();
            $table->bigInteger('stars_prize')->nullable();
            $table->integer('finish_date');
            $table->integer('winners_count');
            $table->integer('activated_count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_924a32fe19e0ea7c1685b935');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_giveaway_info_giveaway_info_results');
        Schema::dropIfExists('tl_payments_giveaway_info_giveaway_info');
        Schema::dropIfExists('tl_payments_giveaway_info');
    }
};
