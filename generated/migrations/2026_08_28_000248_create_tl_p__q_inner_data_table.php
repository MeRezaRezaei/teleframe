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
        Schema::create('tl_p__q_inner_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8b923a0c3bef6586315f8f70');
            $table->index('account_id', 'ix_7f1b4542a1ef16dc0145b474');
        });
        Schema::create('tl_p__q_inner_data_p_q_inner_data_dc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_p__q_inner_data')->cascadeOnDelete();
            $table->text('pq');
            $table->text('p');
            $table->text('q');
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->decimal('new_nonce', 78, 0);
            $table->integer('dc');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4a68b80da68515c987b5c2dc');
        });
        Schema::create('tl_p__q_inner_data_p_q_inner_data_temp_dc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_p__q_inner_data')->cascadeOnDelete();
            $table->text('pq');
            $table->text('p');
            $table->text('q');
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->decimal('new_nonce', 78, 0);
            $table->integer('dc');
            $table->integer('expires_in');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4b61e7aa77ef38215003178c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_p__q_inner_data_p_q_inner_data_temp_dc');
        Schema::dropIfExists('tl_p__q_inner_data_p_q_inner_data_dc');
        Schema::dropIfExists('tl_p__q_inner_data');
    }
};
