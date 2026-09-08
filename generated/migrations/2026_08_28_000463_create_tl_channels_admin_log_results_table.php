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
        Schema::create('tl_channels_admin_log_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_channels_admin_log_results_admin_log_results', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channels_admin_log_results')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_channels_admin_log_results_admin_log_results__events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_admin_log_results_admin_log_results')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_66af4b8561ac75c0b6b6');
        });
        Schema::create('tl_channels_admin_log_results_admin_log_results__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_admin_log_results_admin_log_results')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9242cbf3c8df25934d32');
        });
        Schema::create('tl_channels_admin_log_results_admin_log_results__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_admin_log_results_admin_log_results')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_01b9d253e45f9c2c9b58');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channels_admin_log_results_admin_log_results__users');
        Schema::dropIfExists('tl_channels_admin_log_results_admin_log_results__chats');
        Schema::dropIfExists('tl_channels_admin_log_results_admin_log_results__events');
        Schema::dropIfExists('tl_channels_admin_log_results_admin_log_results');
        Schema::dropIfExists('tl_channels_admin_log_results');
    }
};
