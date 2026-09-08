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
        Schema::create('tl_payments_stars_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_stars_status_stars_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_stars_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('balance');
            $table->text('subscriptions_next_offset')->nullable();
            $table->bigInteger('subscriptions_missing_balance')->nullable();
            $table->text('next_offset')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_payments_stars_status_stars_status__subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_stars_status_stars_status')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8b522994047e25269a57');
        });
        Schema::create('tl_payments_stars_status_stars_status__history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_stars_status_stars_status')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_fdedad58fc14001b086d');
        });
        Schema::create('tl_payments_stars_status_stars_status__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_stars_status_stars_status')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1b1ea0cb05e4d01a7a03');
        });
        Schema::create('tl_payments_stars_status_stars_status__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_stars_status_stars_status')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_743645596d180c0cd7b2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_stars_status_stars_status__users');
        Schema::dropIfExists('tl_payments_stars_status_stars_status__chats');
        Schema::dropIfExists('tl_payments_stars_status_stars_status__history');
        Schema::dropIfExists('tl_payments_stars_status_stars_status__subscriptions');
        Schema::dropIfExists('tl_payments_stars_status_stars_status');
        Schema::dropIfExists('tl_payments_stars_status');
    }
};
