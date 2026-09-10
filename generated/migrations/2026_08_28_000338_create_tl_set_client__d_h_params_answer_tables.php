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
        Schema::create('tl_set_client__d_h_params_answer_dh_gen_fail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->decimal('new_nonce_hash3', 39, 0)->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bba8f69e8485263bb597886b');
            $table->index('account_id', 'ix_fc981f2b6a5bbb223cba0f68');
        });
        Schema::create('tl_set_client__d_h_params_answer_dh_gen_ok', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->decimal('new_nonce_hash1', 39, 0)->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_96adcb14231330e8cffe4b26');
            $table->index('account_id', 'ix_d428066a12f616a95b4d46be');
        });
        Schema::create('tl_set_client__d_h_params_answer_dh_gen_retry', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->decimal('new_nonce_hash2', 39, 0)->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a59e2d3a696f9f855d29b430');
            $table->index('account_id', 'ix_c95ebd84813571c9c081217d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_set_client__d_h_params_answer_dh_gen_retry');
        Schema::dropIfExists('tl_set_client__d_h_params_answer_dh_gen_ok');
        Schema::dropIfExists('tl_set_client__d_h_params_answer_dh_gen_fail');
    }
};
