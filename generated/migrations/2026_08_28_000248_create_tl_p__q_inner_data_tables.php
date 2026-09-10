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
        Schema::create('tl_p__q_inner_data_p_q_inner_data_dc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('pq')->nullable();
            $table->text('p')->nullable();
            $table->text('q')->nullable();
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->decimal('new_nonce', 78, 0)->nullable();
            $table->integer('dc')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dab8fa69543b871e72d56b0e');
            $table->index('account_id', 'ix_4a68b80da68515c987b5c2dc');
        });
        Schema::create('tl_p__q_inner_data_p_q_inner_data_temp_dc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('pq')->nullable();
            $table->text('p')->nullable();
            $table->text('q')->nullable();
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->decimal('new_nonce', 78, 0)->nullable();
            $table->integer('dc')->nullable();
            $table->integer('expires_in')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ea561f3a4e6b9b47043e63a8');
            $table->index('account_id', 'ix_4b61e7aa77ef38215003178c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_p__q_inner_data_p_q_inner_data_temp_dc');
        Schema::dropIfExists('tl_p__q_inner_data_p_q_inner_data_dc');
    }
};
