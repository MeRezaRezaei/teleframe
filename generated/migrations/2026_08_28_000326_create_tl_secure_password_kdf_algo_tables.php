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
        Schema::create('tl_secure_password_kdf_algo_secure_password_k_182db726892d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('salt')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dcb6b813993def1c596294ea');
            $table->index('account_id', 'ix_071da507a010a3a32b9b5a7d');
        });
        Schema::create('tl_secure_password_kdf_algo_secure_password_k_b4962aea68ba', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('salt')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b70c86570e5fe2a808adba27');
            $table->index('account_id', 'ix_7e31ea36161530b574856092');
        });
        Schema::create('tl_secure_password_kdf_algo_secure_password_k_9caca554aa37', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f4ad7384debe34bec09856ef');
            $table->index('account_id', 'ix_0b91497d35232e56b92a2fc0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_password_kdf_algo_secure_password_k_9caca554aa37');
        Schema::dropIfExists('tl_secure_password_kdf_algo_secure_password_k_b4962aea68ba');
        Schema::dropIfExists('tl_secure_password_kdf_algo_secure_password_k_182db726892d');
    }
};
