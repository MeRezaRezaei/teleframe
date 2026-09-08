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
        Schema::create('tl_payments_payment_form', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_payment_form_payment_form', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_form')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_save_credentials')->default(false);
            $table->boolean('password_missing')->default(false);
            $table->bigInteger('form_id');
            $table->bigInteger('bot_id');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->uuid('invoice');
            $table->bigInteger('provider_id');
            $table->text('url');
            $table->text('native_provider')->nullable();
            $table->uuid('native_params')->nullable();
            $table->uuid('saved_info')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_payments_payment_form_payment_form__additional_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_form_payment_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c46494062f61984fec49');
        });
        Schema::create('tl_payments_payment_form_payment_form__saved_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_form_payment_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6ef61b365b7bff5675ba');
        });
        Schema::create('tl_payments_payment_form_payment_form__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_form_payment_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_b590761faf59b7d2e754');
        });
        Schema::create('tl_payments_payment_form_payment_form_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_form')->cascadeOnDelete();
            $table->bigInteger('form_id');
            $table->uuid('invoice');
            $table->timestamps();
        });
        Schema::create('tl_payments_payment_form_payment_form_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_form')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('form_id');
            $table->bigInteger('bot_id');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->uuid('invoice');
            $table->timestamps();
        });
        Schema::create('tl_payments_payment_form_payment_form_stars__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_form_payment_form_stars')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_b4de8ac4299e88541ffa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_payment_form_payment_form_stars__users');
        Schema::dropIfExists('tl_payments_payment_form_payment_form_stars');
        Schema::dropIfExists('tl_payments_payment_form_payment_form_star_gift');
        Schema::dropIfExists('tl_payments_payment_form_payment_form__users');
        Schema::dropIfExists('tl_payments_payment_form_payment_form__saved_credentials');
        Schema::dropIfExists('tl_payments_payment_form_payment_form__additional_methods');
        Schema::dropIfExists('tl_payments_payment_form_payment_form');
        Schema::dropIfExists('tl_payments_payment_form');
    }
};
