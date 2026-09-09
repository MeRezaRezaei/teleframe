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
        Schema::create('tl_bots_access_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8a89eeef80d5d02fbc77b81d');
            $table->index('account_id', 'ix_7c484e448fdb96ebc4b211aa');
        });
        Schema::create('tl_bots_access_settings_access_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bots_access_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('restricted')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e94e0b1d43f9a278d1d4fbdf');
        });
        Schema::create('tl_bots_access_settings_access_settings__add_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bots_access_settings_access_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_07777149b249dfbe7779');
            $table->index('account_id', 'ix_285fbf6a668b00397fa4399d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_access_settings_access_settings__add_users');
        Schema::dropIfExists('tl_bots_access_settings_access_settings');
        Schema::dropIfExists('tl_bots_access_settings');
    }
};
