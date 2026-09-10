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
        Schema::create('tl_boost_boost', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('gift')->default(false);
            $table->boolean('giveaway')->default(false);
            $table->boolean('unclaimed')->default(false);
            $table->text('tl_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_23827ea342484da13a76d401');
            $table->integer('giveaway_msg_id')->nullable();
            $table->integer('date')->nullable();
            $table->integer('expires')->nullable();
            $table->text('used_gift_slug')->nullable();
            $table->integer('multiplier')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6be63c96c2a20b6649bd06f2');
            $table->index('account_id', 'ix_e11789b405577971a696080e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_boost_boost');
    }
};
