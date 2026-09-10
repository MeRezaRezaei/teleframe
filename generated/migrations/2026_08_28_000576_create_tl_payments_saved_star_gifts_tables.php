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
        Schema::create('tl_payments_saved_star_gifts_saved_star_gifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count')->nullable();
            $table->bigInteger('chat_notifications_enabled')->nullable();
            $table->index('chat_notifications_enabled', 'ix_84e79652e88720d65cc7ccd1');
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_999a18e3f27519527766e4ff');
            $table->index('account_id', 'ix_d7dba354262cf42a073d7360');
        });
        Schema::create('tl_payments_saved_star_gifts_saved_star_gifts__gifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_0fe72b0bdc05047b6a2164e1')->references('id')->on('tl_payments_saved_star_gifts_saved_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f0681e5ef0439ba3b8a6');
            $table->index('account_id', 'ix_b57a8ca4f54e69cd3006eb30');
        });
        Schema::create('tl_payments_saved_star_gifts_saved_star_gifts__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_08925baabbb5ed8b1ed10dfe')->references('id')->on('tl_payments_saved_star_gifts_saved_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0d837b57ac0b9b52dd40');
            $table->index('account_id', 'ix_4e5426974279abf2e5f77046');
        });
        Schema::create('tl_payments_saved_star_gifts_saved_star_gifts__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_601a69da0a4f8eaefe2168b6')->references('id')->on('tl_payments_saved_star_gifts_saved_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5fc59fa519c2462ca585');
            $table->index('account_id', 'ix_97385d0eec9a80d9af7ba6a8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_saved_star_gifts_saved_star_gifts__users');
        Schema::dropIfExists('tl_payments_saved_star_gifts_saved_star_gifts__chats');
        Schema::dropIfExists('tl_payments_saved_star_gifts_saved_star_gifts__gifts');
        Schema::dropIfExists('tl_payments_saved_star_gifts_saved_star_gifts');
    }
};
