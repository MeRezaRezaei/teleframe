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
        Schema::create('tl_res_p_q', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8dd2ed85dd8efb65f76a82b0');
            $table->index('account_id', 'ix_89d05b5937a19f9ba2016415');
        });
        Schema::create('tl_res_p_q_res_p_q', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_res_p_q')->cascadeOnDelete();
            $table->decimal('nonce', 39, 0);
            $table->decimal('server_nonce', 39, 0);
            $table->text('pq');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d650c9cd3fb18c58270249ef');
        });
        Schema::create('tl_res_p_q_res_p_q__server_public_key_fingerprints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_res_p_q_res_p_q')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3085b59c201b7adab4d6');
            $table->index('account_id', 'ix_4de9733a32c55fa54b80ff3b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_res_p_q_res_p_q__server_public_key_fingerprints');
        Schema::dropIfExists('tl_res_p_q_res_p_q');
        Schema::dropIfExists('tl_res_p_q');
    }
};
