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
        Schema::create('tl_payments_checked_gift_code_checked_gift_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_giveaway')->default(false);
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_c928b3816834f8c7bf7475bb');
            $table->integer('giveaway_msg_id')->nullable();
            $table->bigInteger('to_id')->nullable();
            $table->index('to_id', 'ix_b2ee9dd37dc0a0c09c238284');
            $table->integer('date')->nullable();
            $table->integer('days')->nullable();
            $table->integer('used_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_df10f564dc9693792098bbb4');
            $table->index('account_id', 'ix_354dea51ff0a552967088e7c');
        });
        Schema::create('tl_payments_checked_gift_code_checked_gift_code__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_9270ad93f8d1a1b08504e9f1')->references('id')->on('tl_payments_checked_gift_code_checked_gift_code')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7f28bc681534f97ad1ea');
            $table->index('account_id', 'ix_8ce6ae73ca9c366ff49fb14a');
        });
        Schema::create('tl_payments_checked_gift_code_checked_gift_code__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_1c6f286d6a684acc4859c9b9')->references('id')->on('tl_payments_checked_gift_code_checked_gift_code')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c30302d6aeb60219fd31');
            $table->index('account_id', 'ix_54fcc2a54cea12b670d215f8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_checked_gift_code_checked_gift_code__users');
        Schema::dropIfExists('tl_payments_checked_gift_code_checked_gift_code__chats');
        Schema::dropIfExists('tl_payments_checked_gift_code_checked_gift_code');
    }
};
