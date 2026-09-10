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
        Schema::create('tl_help_app_update_app_update', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_not_skip')->default(false);
            $table->integer('tl_id')->nullable();
            $table->text('version')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_0b93c5fe1daf21c6d088ec59');
            $table->text('url')->nullable();
            $table->bigInteger('sticker')->nullable();
            $table->index('sticker', 'ix_2a6cf58c8f975d10b4b354c6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ba32be773c5b0e5dfb15cc25');
            $table->index('account_id', 'ix_1a276183f9373b54765bb684');
        });
        Schema::create('tl_help_app_update_app_update__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_230b9b53ddd9a81d8db6627c')->references('id')->on('tl_help_app_update_app_update')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4058f1bc17a486372ee3');
            $table->index('account_id', 'ix_9d01c817eeaec5a519e79867');
        });
        Schema::create('tl_help_app_update_no_app_update', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_03b824c08c3de19af61c3dcb');
            $table->index('account_id', 'ix_3e96b7330501914c77cfa479');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_app_update_no_app_update');
        Schema::dropIfExists('tl_help_app_update_app_update__entities');
        Schema::dropIfExists('tl_help_app_update_app_update');
    }
};
