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
        Schema::create('tl_client__d_h__inner__data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bc88e60575b863842ccdc454');
            $table->index('account_id', 'ix_982d078d6fc6d66fb2e793c9');
        });
        Schema::create('tl_client__d_h__inner__data_client__d_h_inner_data', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_client__d_h__inner__data')->cascadeOnDelete();
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->bigInteger('retry_id');
            $table->index('retry_id', 'ix_0b4548dfa22aa5e3e4150911');
            $table->text('g_b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_162c622ffb3722f6c7e6a17b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_client__d_h__inner__data_client__d_h_inner_data');
        Schema::dropIfExists('tl_client__d_h__inner__data');
    }
};
