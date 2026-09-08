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
        Schema::create('tl_input_store_payment_purpose', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_fcc6e9ee0964', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('restore')->default(false);
            $table->text('phone_number');
            $table->text('phone_code_hash');
            $table->integer('premium_days');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->timestamps();
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_e777086dbb72', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->uuid('user_id');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->timestamps();
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_dd29b020fdd6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('boost_peer')->nullable();
            $table->text('currency');
            $table->bigInteger('amount');
            $table->uuid('message')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_371b3f5239e2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_dd29b020fdd6')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_92b8651cf426bbdf7b2a');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_a2df1d4d4d93', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('winners_are_visible')->default(false);
            $table->uuid('boost_peer');
            $table->text('prize_description')->nullable();
            $table->bigInteger('random_id');
            $table->integer('until_date');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->timestamps();
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_50ddd03f255d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_a2df1d4d4d93')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_26ebe1de0f23ce39bb95');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_91597f6f2753', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_a2df1d4d4d93')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5f9b4ac44227e75fe252');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_45f13b2023bb', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('restore')->default(false);
            $table->boolean('upgrade')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_0bfdc631e6d2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->uuid('user_id');
            $table->bigInteger('stars');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->timestamps();
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_ab10defc70e9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('winners_are_visible')->default(false);
            $table->bigInteger('stars');
            $table->uuid('boost_peer');
            $table->text('prize_description')->nullable();
            $table->bigInteger('random_id');
            $table->integer('until_date');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->integer('users');
            $table->timestamps();
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_d3d3e4e26498', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_ab10defc70e9')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6d84a3bcaea6ea743cd2');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_94f902dbf3fa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_ab10defc70e9')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9d20e229688be7d8141c');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_287cc8447db2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('stars');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->uuid('spend_purpose_peer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_287cc8447db2');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_94f902dbf3fa');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_d3d3e4e26498');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_ab10defc70e9');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_0bfdc631e6d2');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_45f13b2023bb');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_91597f6f2753');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_50ddd03f255d');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_a2df1d4d4d93');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_371b3f5239e2');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_dd29b020fdd6');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_e777086dbb72');
        Schema::dropIfExists('tl_input_store_payment_purpose_input_store_pa_fcc6e9ee0964');
        Schema::dropIfExists('tl_input_store_payment_purpose');
    }
};
