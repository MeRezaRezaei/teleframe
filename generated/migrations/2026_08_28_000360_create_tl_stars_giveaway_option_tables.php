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
        Schema::create('tl_stars_giveaway_option_stars_giveaway_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('extended')->default(false);
            $table->boolean('tl_default')->default(false);
            $table->bigInteger('stars')->nullable();
            $table->integer('yearly_boosts')->nullable();
            $table->text('store_product')->nullable();
            $table->text('currency')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a8e098e5a3c73552a561b85e');
            $table->index('account_id', 'ix_1c534df121bc6284e46fe654');
        });
        Schema::create('tl_stars_giveaway_option_stars_giveaway_option__winners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_03ada73683baf7bca0e4c17f')->references('id')->on('tl_stars_giveaway_option_stars_giveaway_option')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5c8ff6fb0c05937be60e');
            $table->index('account_id', 'ix_1affe908159635e81ff3c5dd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_giveaway_option_stars_giveaway_option__winners');
        Schema::dropIfExists('tl_stars_giveaway_option_stars_giveaway_option');
    }
};
