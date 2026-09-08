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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_bots_access_settings_access_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bots_access_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('restricted')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_bots_access_settings_access_settings__add_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bots_access_settings_access_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_07777149b249dfbe7779');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_access_settings_access_settings__add_users');
        Schema::dropIfExists('tl_bots_access_settings_access_settings');
        Schema::dropIfExists('tl_bots_access_settings');
    }
};
