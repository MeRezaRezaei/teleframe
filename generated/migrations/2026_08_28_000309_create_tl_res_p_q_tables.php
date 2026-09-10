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
        Schema::create('tl_res_p_q_res_p_q', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->decimal('nonce', 39, 0)->nullable();
            $table->decimal('server_nonce', 39, 0)->nullable();
            $table->text('pq')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4ef8cf545cb7a3d53eb0807c');
            $table->index('account_id', 'ix_d650c9cd3fb18c58270249ef');
        });
        Schema::create('tl_res_p_q_res_p_q__server_public_key_fingerprints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_res_p_q_res_p_q', 'id', 'fk_cf8f77f21b923c735981ea00')->cascadeOnDelete();
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
    }
};
