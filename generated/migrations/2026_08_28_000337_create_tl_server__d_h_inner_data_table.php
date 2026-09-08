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
        Schema::create('tl_server__d_h_inner_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_server__d_h_inner_data_server__d_h_inner_data', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_server__d_h_inner_data')->cascadeOnDelete();
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->integer('g');
            $table->text('dh_prime');
            $table->text('g_a');
            $table->integer('server_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_server__d_h_inner_data_server__d_h_inner_data');
        Schema::dropIfExists('tl_server__d_h_inner_data');
    }
};
