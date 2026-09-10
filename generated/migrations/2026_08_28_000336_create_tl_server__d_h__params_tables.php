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
        Schema::create('tl_server__d_h__params_server__d_h_params_fail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->decimal('new_nonce_hash', 39, 0)->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_82b9edd91f148c1451059638');
            $table->index('account_id', 'ix_0f7c1f2ec17a38b8f65fd2e7');
        });
        Schema::create('tl_server__d_h__params_server__d_h_params_ok', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->text('encrypted_answer')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e2d3e6859e11b1ac24ada31a');
            $table->index('account_id', 'ix_97d0a8371f03b06c7c54586a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_server__d_h__params_server__d_h_params_ok');
        Schema::dropIfExists('tl_server__d_h__params_server__d_h_params_fail');
    }
};
