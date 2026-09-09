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
        Schema::create('tl_server__d_h__params', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ba775fa35088a1e4c43756fa');
            $table->index('account_id', 'ix_3a0b2eb591b86f49b08eb907');
        });
        Schema::create('tl_server__d_h__params_server__d_h_params_fail', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_server__d_h__params')->cascadeOnDelete();
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->decimal('new_nonce_hash', 39, 0);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0f7c1f2ec17a38b8f65fd2e7');
        });
        Schema::create('tl_server__d_h__params_server__d_h_params_ok', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_server__d_h__params')->cascadeOnDelete();
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->text('encrypted_answer');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_97d0a8371f03b06c7c54586a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_server__d_h__params_server__d_h_params_ok');
        Schema::dropIfExists('tl_server__d_h__params_server__d_h_params_fail');
        Schema::dropIfExists('tl_server__d_h__params');
    }
};
