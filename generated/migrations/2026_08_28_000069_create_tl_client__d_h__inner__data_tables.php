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
        Schema::create('tl_client__d_h__inner__data_client__d_h_inner_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->bigInteger('retry_id')->nullable();
            $table->index('retry_id', 'ix_0b4548dfa22aa5e3e4150911');
            $table->text('g_b')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5b940fbb98bc433046d97865');
            $table->index('account_id', 'ix_162c622ffb3722f6c7e6a17b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_client__d_h__inner__data_client__d_h_inner_data');
    }
};
