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
        Schema::create('tl_password_kdf_algo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_password_kdf_algo_password_kdf_algo_s_h_a2_ac2e9e239dcc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_password_kdf_algo')->cascadeOnDelete();
            $table->binary('salt1');
            $table->binary('salt2');
            $table->integer('g');
            $table->binary('p');
            $table->timestamps();
        });
        Schema::create('tl_password_kdf_algo_password_kdf_algo_unknown', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_password_kdf_algo')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_password_kdf_algo_password_kdf_algo_unknown');
        Schema::dropIfExists('tl_password_kdf_algo_password_kdf_algo_s_h_a2_ac2e9e239dcc');
        Schema::dropIfExists('tl_password_kdf_algo');
    }
};
