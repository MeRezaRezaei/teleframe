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
            $table->index('constructor_id', 'ix_268e59008c340812cac93c35');
            $table->index('account_id', 'ix_cbc0d3b651526c864bdf4a92');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_all', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c6a39339c628cb30e49ad42b');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cc78ad8f76f13155b8ccf468');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_chat_participants', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e7466729dd38b41fbf09a5c7');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_chat_part_30f33e023df6', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_privacy_rule_privacy_value_allow_chat_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c67ea044f99f2135a6c0');
            $table->index('account_id', 'ix_5c4df9b59a44026ab8de98c7');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_close_friends', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7ec5f78a563e4b6c274a0520');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_contacts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_de7af86e3b646356da6ed4db');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_premium', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ce9048e263fe7fa06f21f65f');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_050c04431d49496fcb1604e9');
        });
        Schema::create('tl_privacy_rule_privacy_value_allow_users__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_privacy_rule_privacy_value_allow_users')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0e54617ce70c05f1823a');
            $table->index('account_id', 'ix_ac56bff324aa04f209ce759c');
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_all', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ad39b7afff01e663ed084930');
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a4c6b3bf46d5221f10afda16');
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_chat_participants', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2e87bda467c45bc3f2263521');
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_chat_p_e44ae86ddb9b', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_privacy_rule_privacy_value_disallow_chat_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_833dbc7518067778e0ff');
            $table->index('account_id', 'ix_22918e3f3c01d1080dc45fc6');
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_contacts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_41520eaf7d8f027261543bbd');
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_privacy_rule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bcda470618e83f4af30c04d9');
        });
        Schema::create('tl_privacy_rule_privacy_value_disallow_users__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_privacy_rule_privacy_value_disallow_users')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_efd83d6c0df628c8ae9b');
            $table->index('account_id', 'ix_e479320de7bfa37d81a930c5');
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
