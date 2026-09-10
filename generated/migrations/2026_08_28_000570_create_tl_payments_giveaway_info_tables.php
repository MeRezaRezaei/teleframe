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
        Schema::create('tl_payments_giveaway_info_giveaway_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('participating')->default(false);
            $table->boolean('preparing_results')->default(false);
            $table->integer('start_date')->nullable();
            $table->integer('joined_too_early_date')->nullable();
            $table->bigInteger('admin_disallowed_chat_id')->nullable();
            $table->index('admin_disallowed_chat_id', 'ix_70ab9d923d2aecf8439736b7');
            $table->text('disallowed_country')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5e0b108e0beba7b5e4579225');
            $table->index('account_id', 'ix_e50a38b37539bbe097fca912');
        });
        Schema::create('tl_payments_giveaway_info_giveaway_info_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('winner')->default(false);
            $table->boolean('refunded')->default(false);
            $table->integer('start_date')->nullable();
            $table->text('gift_code_slug')->nullable();
            $table->bigInteger('stars_prize')->nullable();
            $table->integer('finish_date')->nullable();
            $table->integer('winners_count')->nullable();
            $table->integer('activated_count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_706bf5dece8f38491f1ebfbf');
            $table->index('account_id', 'ix_924a32fe19e0ea7c1685b935');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_giveaway_info_giveaway_info_results');
        Schema::dropIfExists('tl_payments_giveaway_info_giveaway_info');
    }
};
