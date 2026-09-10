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
        Schema::create('tl_server__d_h_inner_data_server__d_h_inner_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->integer('g')->nullable();
            $table->text('dh_prime')->nullable();
            $table->text('g_a')->nullable();
            $table->integer('server_time')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2cd99f6888991de440b38677');
            $table->index('account_id', 'ix_01f9c920476d4e16df3322f5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_server__d_h_inner_data_server__d_h_inner_data');
    }
};
