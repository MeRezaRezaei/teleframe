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
        Schema::create('tl_input_saved_star_gift_input_saved_star_gift_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_115dca6ca050657077495f0a');
            $table->bigInteger('saved_id')->nullable();
            $table->index('saved_id', 'ix_cffde22084ea25dafe7d0e02');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_be186db71d155ce979d6ebad');
            $table->index('account_id', 'ix_ae92762d04055a3da707797c');
        });
        Schema::create('tl_input_saved_star_gift_input_saved_star_gift_slug', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_71c21d6d499cdc651d841866');
            $table->index('account_id', 'ix_b736e28ed74b82b440b99d11');
        });
        Schema::create('tl_input_saved_star_gift_input_saved_star_gift_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('msg_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ff6c7110a361b87de281dbe2');
            $table->index('account_id', 'ix_e1d37edda9958eb9b2e7108a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_saved_star_gift_input_saved_star_gift_user');
        Schema::dropIfExists('tl_input_saved_star_gift_input_saved_star_gift_slug');
        Schema::dropIfExists('tl_input_saved_star_gift_input_saved_star_gift_chat');
    }
};
