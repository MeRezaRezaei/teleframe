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
        Schema::create('tl_payments_payment_form_payment_form', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_save_credentials')->default(false);
            $table->boolean('password_missing')->default(false);
            $table->bigInteger('form_id')->nullable();
            $table->index('form_id', 'ix_46fa2af1aaadcc86d5df9870');
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_75a0b45b547e6194e2fa2bb3');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_000428349bb88d878581c448');
            $table->bigInteger('invoice')->nullable();
            $table->index('invoice', 'ix_00e7453d7e95a9038c9de36f');
            $table->bigInteger('provider_id')->nullable();
            $table->index('provider_id', 'ix_e0143713eb64a326f8a87e92');
            $table->text('url')->nullable();
            $table->text('native_provider')->nullable();
            $table->bigInteger('native_params')->nullable();
            $table->index('native_params', 'ix_041f4ab53b904c2d8fb0d6a2');
            $table->bigInteger('saved_info')->nullable();
            $table->index('saved_info', 'ix_ae5981831a2f41e88f445ab0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5fea490bf8288490a9620ee4');
            $table->index('account_id', 'ix_8a16a11bdaf4407d841dde01');
        });
        Schema::create('tl_payments_payment_form_payment_form__additional_methods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c9ea9b2d91e4a26c8b8a0c5b')->references('id')->on('tl_payments_payment_form_payment_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c46494062f61984fec49');
            $table->index('account_id', 'ix_77a45be6bf227ad2b6f10a83');
        });
        Schema::create('tl_payments_payment_form_payment_form__saved_credentials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_6c17033c68add14fea1a64b3')->references('id')->on('tl_payments_payment_form_payment_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6ef61b365b7bff5675ba');
            $table->index('account_id', 'ix_d49d80e6888dc3b293f0d86b');
        });
        Schema::create('tl_payments_payment_form_payment_form__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_5f3661d55d53e7215121141f')->references('id')->on('tl_payments_payment_form_payment_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b590761faf59b7d2e754');
            $table->index('account_id', 'ix_4f7d0da7c58eedc811c650e1');
        });
        Schema::create('tl_payments_payment_form_payment_form_star_gift', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('form_id')->nullable();
            $table->index('form_id', 'ix_ed8bcf4bcca5c4ea65148e52');
            $table->bigInteger('invoice')->nullable();
            $table->index('invoice', 'ix_171f55d2077e978736781f4c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a41aaa977525df2513f2f7a2');
            $table->index('account_id', 'ix_a9e195c2841af4e08d696c6c');
        });
        Schema::create('tl_payments_payment_form_payment_form_stars', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('form_id')->nullable();
            $table->index('form_id', 'ix_94556a793f5fb8b6f0061be1');
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_174904eaddda45f6fc0927f9');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_788c8a319fea566748bcdddd');
            $table->bigInteger('invoice')->nullable();
            $table->index('invoice', 'ix_951a809abe334938734802e3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b733d4b7cd483163bfd02113');
            $table->index('account_id', 'ix_cc2af88e4a0fb87066aebf79');
        });
        Schema::create('tl_payments_payment_form_payment_form_stars__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_1737ce34c194d155319b69cc')->references('id')->on('tl_payments_payment_form_payment_form_stars')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
