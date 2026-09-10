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
        Schema::create('tl_messages_high_scores_high_scores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_037ab6a67eff727175820b81');
            $table->index('account_id', 'ix_f5d34beec745e5449db398d5');
        });
        Schema::create('tl_messages_high_scores_high_scores__scores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_4f12fd4bb53df88e358e1766')->references('id')->on('tl_messages_high_scores_high_scores')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b628aa4fe56d296c7b55');
            $table->index('account_id', 'ix_e3d490606af11e3423234502');
        });
        Schema::create('tl_messages_high_scores_high_scores__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_b33dfdd91c13bcd950481004')->references('id')->on('tl_messages_high_scores_high_scores')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8b78be3854d6c48cd875');
            $table->index('account_id', 'ix_e92938a10141d16a05895fb2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_high_scores_high_scores__users');
        Schema::dropIfExists('tl_messages_high_scores_high_scores__scores');
        Schema::dropIfExists('tl_messages_high_scores_high_scores');
    }
};
