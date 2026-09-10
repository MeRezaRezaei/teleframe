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
        Schema::create('tl_input_business_chat_link_input_business_chat_link', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('message')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e5ac256c68ce2047da027da3');
            $table->index('account_id', 'ix_f5a4d10d2697f47d00f80d9e');
        });
        Schema::create('tl_input_business_chat_link_input_business_ch_2c7a8d471020', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_bdad726611dfcf74b1ea50a8')->references('id')->on('tl_input_business_chat_link_input_business_chat_link')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ca413363b0cc9f76ae0b');
            $table->index('account_id', 'ix_6b302f8e16024e1c1b807206');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_chat_link_input_business_ch_2c7a8d471020');
        Schema::dropIfExists('tl_input_business_chat_link_input_business_chat_link');
    }
};
