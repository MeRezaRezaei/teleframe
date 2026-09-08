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
        Schema::create('tl_bot_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_bot_info_bot_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_preview_medias')->default(false);
            $table->bigInteger('user_id')->nullable();
            $table->text('description')->nullable();
            $table->uuid('description_photo')->nullable();
            $table->uuid('description_document')->nullable();
            $table->uuid('menu_button')->nullable();
            $table->text('privacy_policy_url')->nullable();
            $table->uuid('app_settings')->nullable();
            $table->uuid('verifier_settings')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_bot_info_bot_info__commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bot_info_bot_info')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_83b2913c3a7e547726ab');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_info_bot_info__commands');
        Schema::dropIfExists('tl_bot_info_bot_info');
        Schema::dropIfExists('tl_bot_info');
    }
};
