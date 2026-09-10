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
        Schema::create('tl_payments_unique_star_gift_unique_star_gift', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('gift')->nullable();
            $table->index('gift', 'ix_2be7621ec13a36d5b86499e5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1c1ea2f2fde4ca1ea1183590');
            $table->index('account_id', 'ix_c4a9ddbad01a7584afdfe19a');
        });
        Schema::create('tl_payments_unique_star_gift_unique_star_gift__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_unique_star_gift_unique_star_gift', 'id', 'fk_e52be003ee580a634723bc35')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f549e291516938108305');
            $table->index('account_id', 'ix_e1f140b3fc3dde4fbc92a17e');
        });
        Schema::create('tl_payments_unique_star_gift_unique_star_gift__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_unique_star_gift_unique_star_gift', 'id', 'fk_23989fe7246980d558c59bd3')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_67136748af6c1cb5e875');
            $table->index('account_id', 'ix_cbd39210294e97a772310d3a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_unique_star_gift_unique_star_gift__users');
        Schema::dropIfExists('tl_payments_unique_star_gift_unique_star_gift__chats');
        Schema::dropIfExists('tl_payments_unique_star_gift_unique_star_gift');
    }
};
