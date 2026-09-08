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
        Schema::create('tl_input_privacy_rule', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_all', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_all_b839cc5564e5', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_all_c89442bd25b2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_privacy_rule_input_privacy_value_all_b839cc5564e5')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d9ead65f48235e2c0871');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_all_4e73532530d9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_contacts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_premium', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_allow_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_all_71681af9150e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_privacy_rule_input_privacy_value_allow_users')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6d73582cc6fa315e6f3a');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_disallow_all', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_disallow_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_dis_92dd14476e43', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_dis_bb41d2b871d8', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_privacy_rule_input_privacy_value_dis_92dd14476e43')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_dcc5fa33f524e940c213');
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_dis_8a366d0a4f14', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_disallow_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_privacy_rule')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_privacy_rule_input_privacy_value_dis_d8b619151246', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_privacy_rule_input_privacy_value_disallow_users')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_047e252645687cc19c0e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_dis_d8b619151246');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_disallow_users');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_dis_8a366d0a4f14');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_dis_bb41d2b871d8');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_dis_92dd14476e43');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_disallow_bots');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_disallow_all');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_all_71681af9150e');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_users');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_premium');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_contacts');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_all_4e73532530d9');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_all_c89442bd25b2');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_all_b839cc5564e5');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_bots');
        Schema::dropIfExists('tl_input_privacy_rule_input_privacy_value_allow_all');
        Schema::dropIfExists('tl_input_privacy_rule');
    }
};
