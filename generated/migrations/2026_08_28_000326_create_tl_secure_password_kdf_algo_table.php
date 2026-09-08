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
        Schema::create('tl_secure_password_kdf_algo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_secure_password_kdf_algo_secure_password_k_182db726892d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_password_kdf_algo')->cascadeOnDelete();
            $table->binary('salt');
            $table->timestamps();
        });
        Schema::create('tl_secure_password_kdf_algo_secure_password_k_b4962aea68ba', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_password_kdf_algo')->cascadeOnDelete();
            $table->binary('salt');
            $table->timestamps();
        });
        Schema::create('tl_secure_password_kdf_algo_secure_password_k_9caca554aa37', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_password_kdf_algo')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_password_kdf_algo_secure_password_k_9caca554aa37');
        Schema::dropIfExists('tl_secure_password_kdf_algo_secure_password_k_b4962aea68ba');
        Schema::dropIfExists('tl_secure_password_kdf_algo_secure_password_k_182db726892d');
        Schema::dropIfExists('tl_secure_password_kdf_algo');
    }
};
