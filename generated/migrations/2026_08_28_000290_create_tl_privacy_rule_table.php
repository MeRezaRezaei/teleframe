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
        Schema::create('tl_privacy_rule', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_all', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_chat_participants', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_chat_part_30f33e023df6', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_privacy_rule_privacy_value_allow_chat_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c67ea044f99f2135a6c0');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_close_friends', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_contacts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_premium', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_users__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_privacy_rule_privacy_value_allow_users')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0e54617ce70c05f1823a');
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_all', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_chat_participants', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_chat_p_e44ae86ddb9b', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_privacy_rule_privacy_value_disallow_chat_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_833dbc7518067778e0ff');
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_contacts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_users__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_privacy_rule_privacy_value_disallow_users')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_efd83d6c0df628c8ae9b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_privacy_rule_privacy_value_disallow_users__users');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_disallow_users');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_disallow_contacts');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_disallow_chat_p_e44ae86ddb9b');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_disallow_chat_participants');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_disallow_bots');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_disallow_all');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_allow_users__users');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_allow_users');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_allow_premium');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_allow_contacts');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_allow_close_friends');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_allow_chat_part_30f33e023df6');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_allow_chat_participants');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_allow_bots');
        Schema::dropIfExists('tl_privacy_rule_privacy_value_allow_all');
        Schema::dropIfExists('tl_privacy_rule');
    }
};
