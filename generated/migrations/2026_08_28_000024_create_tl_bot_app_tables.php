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
        Schema::create('tl_bot_app_bot_app', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->text('short_name')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_f6ce3aaaf9d77a145ca2fc7d');
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_05633c5c0ba5052d3467bc8e');
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_eba016b81ceec463fe8566ed');
            $table->index('account_id', 'ix_b60d48f37e646b0c6fd085c4');
        });
        Schema::create('tl_bot_app_bot_app_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c1750ab45bcb3b6c099bcfbe');
            $table->index('account_id', 'ix_92459e5838a986335501104b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_app_bot_app_not_modified');
        Schema::dropIfExists('tl_bot_app_bot_app');
    }
};
