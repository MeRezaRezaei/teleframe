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
        Schema::create('tl_j_s_o_n_value_json_array', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2582c283bd7ba42633db237b');
            $table->index('account_id', 'ix_296df74f73c46c1fa32b55eb');
        });
        Schema::create('tl_j_s_o_n_value_json_array__value', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_j_s_o_n_value_json_array', 'id', 'fk_b82d136f398dcad7d4b810ea')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8da3e9f1c8ec748e5703');
            $table->index('account_id', 'ix_61b521ef6a7ebc8029331673');
        });
        Schema::create('tl_j_s_o_n_value_json_bool', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_value')->nullable();
            $table->index('tl_value', 'ix_debceeffcc0a9a190ccf95d0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7f99281fb2e615c0d98ca3b5');
            $table->index('account_id', 'ix_0445d96b2e6e935e70a17b41');
        });
        Schema::create('tl_j_s_o_n_value_json_null', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_12de4372b09e6e9a4580f3e7');
            $table->index('account_id', 'ix_3b379c3092321938bad7a50b');
        });
        Schema::create('tl_j_s_o_n_value_json_number', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->double('tl_value')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3a3ff2eeb438f7f74f1f6485');
            $table->index('account_id', 'ix_9cc1b2faa16b33a0663ede39');
        });
        Schema::create('tl_j_s_o_n_value_json_object', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d31fb1e9ca6fa3254ff95443');
            $table->index('account_id', 'ix_45965a72d988a0b390b8479e');
        });
        Schema::create('tl_j_s_o_n_value_json_object__value', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_j_s_o_n_value_json_object', 'id', 'fk_f80a9cd59d9f573eecb1f3fd')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1f8936bac12d45721056');
            $table->index('account_id', 'ix_ab2a7c6823496087bab60675');
        });
        Schema::create('tl_j_s_o_n_value_json_string', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_value')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6cfc197a3d6a3d3901700937');
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
    }
};
