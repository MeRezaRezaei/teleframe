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
        Schema::create('tl_data_j_s_o_n', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_16665586261c99e79f21662a');
            $table->index('account_id', 'ix_ab4da02244bf74ca2d72758b');
        });
        Schema::create('tl_data_j_s_o_n_data_j_s_o_n', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_data_j_s_o_n')->cascadeOnDelete();
            $table->text('data');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_82cf83d51db6800e9b8acb05');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_data_j_s_o_n_data_j_s_o_n');
        Schema::dropIfExists('tl_data_j_s_o_n');
    }
};
