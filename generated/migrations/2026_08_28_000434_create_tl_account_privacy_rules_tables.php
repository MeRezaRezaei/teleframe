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
        Schema::create('tl_account_privacy_rules_privacy_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_aac8560145b1d854460c02cf');
            $table->index('account_id', 'ix_0040d53866983e04e15d53ae');
        });
        Schema::create('tl_account_privacy_rules_privacy_rules__rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_dd7359a1125c9617ff22e371')->references('id')->on('tl_account_privacy_rules_privacy_rules')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1e1ba867fe5c26e9be4e');
            $table->index('account_id', 'ix_0d109c62f9ecc76b10afbefb');
        });
        Schema::create('tl_account_privacy_rules_privacy_rules__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_787008df2d2b9539e4bc19a9')->references('id')->on('tl_account_privacy_rules_privacy_rules')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d240760e0cadfbec04cb');
            $table->index('account_id', 'ix_bfabf947add6cef45fa14231');
        });
        Schema::create('tl_account_privacy_rules_privacy_rules__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_9deb0410bc602c736a3ebf33')->references('id')->on('tl_account_privacy_rules_privacy_rules')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0562a98351260f5c0444');
            $table->index('account_id', 'ix_5ce47f6a0c780fd4730023ca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_privacy_rules_privacy_rules__users');
        Schema::dropIfExists('tl_account_privacy_rules_privacy_rules__chats');
        Schema::dropIfExists('tl_account_privacy_rules_privacy_rules__rules');
        Schema::dropIfExists('tl_account_privacy_rules_privacy_rules');
    }
};
