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
        Schema::create('tl_r_s_a_public_key', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_788c596e7d3e0017c253a317');
            $table->index('account_id', 'ix_550954f21610a8ae04e4cd08');
        });
        Schema::create('tl_r_s_a_public_key_rsa_public_key', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_r_s_a_public_key')->cascadeOnDelete();
            $table->text('n');
            $table->text('e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ff1621c71f72d1643b2b8077');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_r_s_a_public_key_rsa_public_key');
        Schema::dropIfExists('tl_r_s_a_public_key');
    }
};
