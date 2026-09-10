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
        Schema::create('tl_bots_popular_app_bots_popular_app_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_65418b0e7c5435e41c9ec4bd');
            $table->index('account_id', 'ix_3a5ecb9ac5c2bd33121ce8c2');
        });
        Schema::create('tl_bots_popular_app_bots_popular_app_bots__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_df233f86c3788cf03b985d79')->references('id')->on('tl_bots_popular_app_bots_popular_app_bots')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_09b08ad1aa95887ed468');
            $table->index('account_id', 'ix_5ff7c474bec6ae553a5e40af');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_popular_app_bots_popular_app_bots__users');
        Schema::dropIfExists('tl_bots_popular_app_bots_popular_app_bots');
    }
};
