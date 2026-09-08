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
        Schema::create('tl_account_privacy_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_account_privacy_rules_privacy_rules', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_privacy_rules')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_account_privacy_rules_privacy_rules__rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_privacy_rules_privacy_rules')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1e1ba867fe5c26e9be4e');
        });
        Schema::create('tl_account_privacy_rules_privacy_rules__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_privacy_rules_privacy_rules')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d240760e0cadfbec04cb');
        });
        Schema::create('tl_account_privacy_rules_privacy_rules__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_privacy_rules_privacy_rules')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0562a98351260f5c0444');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_privacy_rules_privacy_rules__users');
        Schema::dropIfExists('tl_account_privacy_rules_privacy_rules__chats');
        Schema::dropIfExists('tl_account_privacy_rules_privacy_rules__rules');
        Schema::dropIfExists('tl_account_privacy_rules_privacy_rules');
        Schema::dropIfExists('tl_account_privacy_rules');
    }
};
