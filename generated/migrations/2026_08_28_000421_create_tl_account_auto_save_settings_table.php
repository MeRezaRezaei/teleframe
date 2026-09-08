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
        Schema::create('tl_account_auto_save_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_account_auto_save_settings_auto_save_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_auto_save_settings')->cascadeOnDelete();
            $table->uuid('users_settings');
            $table->uuid('chats_settings');
            $table->uuid('broadcasts_settings');
            $table->timestamps();
        });
        Schema::create('tl_account_auto_save_settings_auto_save_setti_b5d6efe7410e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_auto_save_settings_auto_save_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e840d7b68eacecb6c048');
        });
        Schema::create('tl_account_auto_save_settings_auto_save_settings__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_auto_save_settings_auto_save_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_eda08881bc7cca58877f');
        });
        Schema::create('tl_account_auto_save_settings_auto_save_settings__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_auto_save_settings_auto_save_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3e6451224e6de342e573');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_auto_save_settings_auto_save_settings__users');
        Schema::dropIfExists('tl_account_auto_save_settings_auto_save_settings__chats');
        Schema::dropIfExists('tl_account_auto_save_settings_auto_save_setti_b5d6efe7410e');
        Schema::dropIfExists('tl_account_auto_save_settings_auto_save_settings');
        Schema::dropIfExists('tl_account_auto_save_settings');
    }
};
