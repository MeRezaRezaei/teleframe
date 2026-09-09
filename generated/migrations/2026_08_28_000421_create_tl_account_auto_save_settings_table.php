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
            $table->index('constructor_id', 'ix_2ee081879fac388695fb1371');
            $table->index('account_id', 'ix_af2f62147f6e180489061bc8');
        });
        Schema::create('tl_account_auto_save_settings_auto_save_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_auto_save_settings')->cascadeOnDelete();
            $table->uuid('users_settings');
            $table->index('users_settings', 'ix_e953ec2c6faaf077fb9d4781');
            $table->uuid('chats_settings');
            $table->index('chats_settings', 'ix_d678f60005a8ffce4516c3a4');
            $table->uuid('broadcasts_settings');
            $table->index('broadcasts_settings', 'ix_47a8906cb8b8ef52a0859982');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9d1c075040ea89f683a0722e');
        });
        Schema::create('tl_account_auto_save_settings_auto_save_setti_b5d6efe7410e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_auto_save_settings_auto_save_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e840d7b68eacecb6c048');
            $table->index('account_id', 'ix_6205a400f4a397593d220bdc');
        });
        Schema::create('tl_account_auto_save_settings_auto_save_settings__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_auto_save_settings_auto_save_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_eda08881bc7cca58877f');
            $table->index('account_id', 'ix_45277ef5cd598326e0c3475a');
        });
        Schema::create('tl_account_auto_save_settings_auto_save_settings__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_auto_save_settings_auto_save_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3e6451224e6de342e573');
            $table->index('account_id', 'ix_818cbca6b8398edb7d4e71f5');
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
