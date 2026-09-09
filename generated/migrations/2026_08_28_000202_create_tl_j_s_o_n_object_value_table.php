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
        Schema::create('tl_j_s_o_n_object_value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2bece309f1f2433a7e3a4bb9');
            $table->index('account_id', 'ix_d1cab9ed93d3d11eb0abab1b');
        });
        Schema::create('tl_j_s_o_n_object_value_json_object_value', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_j_s_o_n_object_value')->cascadeOnDelete();
            $table->text('tl_key');
            $table->uuid('tl_value');
            $table->index('tl_value', 'ix_11ac108b3ed03e699fcbffbf');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_989be87dbaf6851568a3d6f6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_j_s_o_n_object_value_json_object_value');
        Schema::dropIfExists('tl_j_s_o_n_object_value');
    }
};
