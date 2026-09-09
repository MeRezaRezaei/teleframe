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
        Schema::create('tl_destroy_session_res', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6a01d71a0bdd3fd0d83414fc');
            $table->index('account_id', 'ix_e45bb21970f60e0b079ad42a');
        });
        Schema::create('tl_destroy_session_res_destroy_session_none', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_destroy_session_res')->cascadeOnDelete();
            $table->bigInteger('session_id');
            $table->index('session_id', 'ix_ad2912a20a4b3b18821e91f8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_53d959e449747f57ae34549d');
        });
        Schema::create('tl_destroy_session_res_destroy_session_ok', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_destroy_session_res')->cascadeOnDelete();
            $table->bigInteger('session_id');
            $table->index('session_id', 'ix_f22bc0598c59dca23703b1c8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_370e6ecc547a826e2afbf74e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_destroy_session_res_destroy_session_ok');
        Schema::dropIfExists('tl_destroy_session_res_destroy_session_none');
        Schema::dropIfExists('tl_destroy_session_res');
    }
};
