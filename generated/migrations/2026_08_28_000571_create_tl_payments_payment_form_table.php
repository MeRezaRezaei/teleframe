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
            $table->index('constructor_id', 'ix_28d5102b4254b31ca420ae3c');
            $table->index('account_id', 'ix_f3b12bb1c04b5701844d497e');
        });
        Schema::create('tl_payments_payment_form_payment_form', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_form')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_save_credentials')->default(false);
            $table->boolean('password_missing')->default(false);
            $table->bigInteger('form_id');
            $table->index('form_id', 'ix_46fa2af1aaadcc86d5df9870');
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_75a0b45b547e6194e2fa2bb3');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_000428349bb88d878581c448');
            $table->uuid('invoice');
            $table->index('invoice', 'ix_00e7453d7e95a9038c9de36f');
            $table->bigInteger('provider_id');
            $table->index('provider_id', 'ix_e0143713eb64a326f8a87e92');
            $table->text('url');
            $table->text('native_provider')->nullable();
            $table->uuid('native_params')->nullable();
            $table->index('native_params', 'ix_041f4ab53b904c2d8fb0d6a2');
            $table->uuid('saved_info')->nullable();
            $table->index('saved_info', 'ix_ae5981831a2f41e88f445ab0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8a16a11bdaf4407d841dde01');
        });
        Schema::create('tl_payments_payment_form_payment_form__additional_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_form_payment_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c46494062f61984fec49');
            $table->index('account_id', 'ix_77a45be6bf227ad2b6f10a83');
        });
        Schema::create('tl_payments_payment_form_payment_form__saved_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_form_payment_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6ef61b365b7bff5675ba');
            $table->index('account_id', 'ix_d49d80e6888dc3b293f0d86b');
        });
        Schema::create('tl_payments_payment_form_payment_form__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_form_payment_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b590761faf59b7d2e754');
            $table->index('account_id', 'ix_4f7d0da7c58eedc811c650e1');
        });
        Schema::create('tl_payments_payment_form_payment_form_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_form')->cascadeOnDelete();
            $table->bigInteger('form_id');
            $table->index('form_id', 'ix_ed8bcf4bcca5c4ea65148e52');
            $table->uuid('invoice');
            $table->index('invoice', 'ix_171f55d2077e978736781f4c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a9e195c2841af4e08d696c6c');
        });
        Schema::create('tl_payments_payment_form_payment_form_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_form')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('form_id');
            $table->index('form_id', 'ix_94556a793f5fb8b6f0061be1');
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_174904eaddda45f6fc0927f9');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_788c8a319fea566748bcdddd');
            $table->uuid('invoice');
            $table->index('invoice', 'ix_951a809abe334938734802e3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cc2af88e4a0fb87066aebf79');
        });
        Schema::create('tl_payments_payment_form_payment_form_stars__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_form_payment_form_stars')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b4de8ac4299e88541ffa');
            $table->index('account_id', 'ix_1980e48cfbe4878db3ea5510');
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
