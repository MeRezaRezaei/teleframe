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
        Schema::create('tl_j_s_o_n_value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a2281076e4a3bd64ac28839a');
            $table->index('account_id', 'ix_93b0bc9a276ef09559af4255');
        });
        Schema::create('tl_j_s_o_n_value_json_array', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_296df74f73c46c1fa32b55eb');
        });
        Schema::create('tl_j_s_o_n_value_json_array__value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_j_s_o_n_value_json_array')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8da3e9f1c8ec748e5703');
            $table->index('account_id', 'ix_61b521ef6a7ebc8029331673');
        });
        Schema::create('tl_j_s_o_n_value_json_bool', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->uuid('tl_value');
            $table->index('tl_value', 'ix_debceeffcc0a9a190ccf95d0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0445d96b2e6e935e70a17b41');
        });
        Schema::create('tl_j_s_o_n_value_json_null', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3b379c3092321938bad7a50b');
        });
        Schema::create('tl_j_s_o_n_value_json_number', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->double('tl_value');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9cc1b2faa16b33a0663ede39');
        });
        Schema::create('tl_j_s_o_n_value_json_object', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_45965a72d988a0b390b8479e');
        });
        Schema::create('tl_j_s_o_n_value_json_object__value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_j_s_o_n_value_json_object')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1f8936bac12d45721056');
            $table->index('account_id', 'ix_ab2a7c6823496087bab60675');
        });
        Schema::create('tl_j_s_o_n_value_json_string', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->text('tl_value');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_326d40ab04be68af9e3dafc3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_j_s_o_n_value_json_string');
        Schema::dropIfExists('tl_j_s_o_n_value_json_object__value');
        Schema::dropIfExists('tl_j_s_o_n_value_json_object');
        Schema::dropIfExists('tl_j_s_o_n_value_json_number');
        Schema::dropIfExists('tl_j_s_o_n_value_json_null');
        Schema::dropIfExists('tl_j_s_o_n_value_json_bool');
        Schema::dropIfExists('tl_j_s_o_n_value_json_array__value');
        Schema::dropIfExists('tl_j_s_o_n_value_json_array');
        Schema::dropIfExists('tl_j_s_o_n_value');
    }
};
