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
        Schema::create('tl_help_promo_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_promo_data_promo_data', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_promo_data')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('proxy')->default(false);
            $table->integer('expires');
            $table->uuid('peer')->nullable();
            $table->text('psa_type')->nullable();
            $table->text('psa_message')->nullable();
            $table->uuid('custom_pending_suggestion')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_help_promo_data_promo_data__pending_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_promo_data_promo_data')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_efa746198f1f88861449');
        });
        Schema::create('tl_help_promo_data_promo_data__dismissed_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_promo_data_promo_data')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_833f5cba9c46ecaf206b');
        });
        Schema::create('tl_help_promo_data_promo_data__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_promo_data_promo_data')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_df9e0a679fd3409d1682');
        });
        Schema::create('tl_help_promo_data_promo_data__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_promo_data_promo_data')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2d3fbca0e737e13eed68');
        });
        Schema::create('tl_help_promo_data_promo_data_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_promo_data')->cascadeOnDelete();
            $table->integer('expires');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_promo_data_promo_data_empty');
        Schema::dropIfExists('tl_help_promo_data_promo_data__users');
        Schema::dropIfExists('tl_help_promo_data_promo_data__chats');
        Schema::dropIfExists('tl_help_promo_data_promo_data__dismissed_suggestions');
        Schema::dropIfExists('tl_help_promo_data_promo_data__pending_suggestions');
        Schema::dropIfExists('tl_help_promo_data_promo_data');
        Schema::dropIfExists('tl_help_promo_data');
    }
};
