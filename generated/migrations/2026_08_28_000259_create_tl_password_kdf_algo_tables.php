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
        Schema::create('tl_password_kdf_algo_password_kdf_algo_s_h_a2_ac2e9e239dcc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('salt1')->nullable();
            $table->binary('salt2')->nullable();
            $table->integer('g')->nullable();
            $table->binary('p')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a4f883c71ba430fd1040dd47');
            $table->index('account_id', 'ix_f1b74e3b4de35c53329f369a');
        });
        Schema::create('tl_password_kdf_algo_password_kdf_algo_unknown', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_52bb0c17a30472c2a0b016a9');
            $table->index('account_id', 'ix_72eefc4f74bbbc23e7d4b9fc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_password_kdf_algo_password_kdf_algo_unknown');
        Schema::dropIfExists('tl_password_kdf_algo_password_kdf_algo_s_h_a2_ac2e9e239dcc');
    }
};
