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
        Schema::create('tl_set_client__d_h_params_answer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8f5a8b53ba2c8a905c665e82');
            $table->index('account_id', 'ix_d5f1d8a5445479dc5d41d087');
        });
        Schema::create('tl_set_client__d_h_params_answer_dh_gen_fail', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_set_client__d_h_params_answer')->cascadeOnDelete();
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->decimal('new_nonce_hash3', 39, 0);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fc981f2b6a5bbb223cba0f68');
        });
        Schema::create('tl_set_client__d_h_params_answer_dh_gen_ok', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_set_client__d_h_params_answer')->cascadeOnDelete();
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->decimal('new_nonce_hash1', 39, 0);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d428066a12f616a95b4d46be');
        });
        Schema::create('tl_set_client__d_h_params_answer_dh_gen_retry', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_set_client__d_h_params_answer')->cascadeOnDelete();
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->decimal('new_nonce_hash2', 39, 0);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c95ebd84813571c9c081217d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_set_client__d_h_params_answer_dh_gen_retry');
        Schema::dropIfExists('tl_set_client__d_h_params_answer_dh_gen_ok');
        Schema::dropIfExists('tl_set_client__d_h_params_answer_dh_gen_fail');
        Schema::dropIfExists('tl_set_client__d_h_params_answer');
    }
};
