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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_j_s_o_n_value_json_array', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_j_s_o_n_value_json_array__value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_j_s_o_n_value_json_array')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8da3e9f1c8ec748e5703');
        });
        Schema::create('tl_j_s_o_n_value_json_bool', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->uuid('tl_value');
            $table->timestamps();
        });
        Schema::create('tl_j_s_o_n_value_json_null', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_j_s_o_n_value_json_number', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->double('tl_value');
            $table->timestamps();
        });
        Schema::create('tl_j_s_o_n_value_json_object', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_j_s_o_n_value_json_object__value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_j_s_o_n_value_json_object')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1f8936bac12d45721056');
        });
        Schema::create('tl_j_s_o_n_value_json_string', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_value')->cascadeOnDelete();
            $table->text('tl_value');
            $table->timestamps();
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
