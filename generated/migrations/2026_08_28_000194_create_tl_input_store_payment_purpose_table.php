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
            $table->index('constructor_id', 'ix_5f77b2b8a88368da150c87e1');
            $table->index('account_id', 'ix_b82d0fac6623d18ca9b781ab');
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
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ceb8a49b8b3eb4900e3153ed');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_e777086dbb72', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->uuid('user_id');
            $table->index('user_id', 'ix_b890b64b9f57d9f75c9f0904');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7ffc224b2ac325993ed8889e');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_dd29b020fdd6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('boost_peer')->nullable();
            $table->index('boost_peer', 'ix_4b1264b443dc0169f1b1c37e');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->uuid('message')->nullable();
            $table->index('message', 'ix_300ff8b832158b80ddbdaa3b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f27f446a3b51cb40609a2342');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_371b3f5239e2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_dd29b020fdd6')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_92b8651cf426bbdf7b2a');
            $table->index('account_id', 'ix_5cb1a383d40a2869be77f7a6');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_a2df1d4d4d93', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('winners_are_visible')->default(false);
            $table->bigInteger('boost_peer');
            $table->index('boost_peer', 'ix_5ab580d60c7c84a94c241807');
            $table->text('prize_description')->nullable();
            $table->bigInteger('random_id');
            $table->index('random_id', 'ix_ead8b9544ad6ee423de2caf3');
            $table->integer('until_date');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7d7614348ad1e034fcaa9a14');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_50ddd03f255d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_a2df1d4d4d93')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_26ebe1de0f23ce39bb95');
            $table->index('account_id', 'ix_232b18ca4ff5829cd0df6f86');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_91597f6f2753', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_a2df1d4d4d93')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5f9b4ac44227e75fe252');
            $table->index('account_id', 'ix_9b82381b8611bd6149a3a31f');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_45f13b2023bb', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('restore')->default(false);
            $table->boolean('upgrade')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cf334bfb242eb9f2a965f488');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_0bfdc631e6d2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->uuid('user_id');
            $table->index('user_id', 'ix_86368f8e6f821cf35ac7a8e3');
            $table->bigInteger('stars');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f77d1c8714a2e4b7abec2102');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_ab10defc70e9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('winners_are_visible')->default(false);
            $table->bigInteger('stars');
            $table->bigInteger('boost_peer');
            $table->index('boost_peer', 'ix_f5fa6003d1215cc6cad88fae');
            $table->text('prize_description')->nullable();
            $table->bigInteger('random_id');
            $table->index('random_id', 'ix_83ff44ca5c2e0ae4266fec50');
            $table->integer('until_date');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->integer('users');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1fb9e660fe2f02669a114704');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_d3d3e4e26498', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_ab10defc70e9')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d84a3bcaea6ea743cd2');
            $table->index('account_id', 'ix_05eea197b5762b51519999b6');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_94f902dbf3fa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_store_payment_purpose_input_store_pa_ab10defc70e9')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9d20e229688be7d8141c');
            $table->index('account_id', 'ix_8c0acbdd83702fd959c6162b');
        });
        Schema::create('tl_input_store_payment_purpose_input_store_pa_287cc8447db2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_store_payment_purpose')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('stars');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->bigInteger('spend_purpose_peer')->nullable();
            $table->index('spend_purpose_peer', 'ix_97062e396af682d1152979ce');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ca20edf4f4ef0894bb2e5539');
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
