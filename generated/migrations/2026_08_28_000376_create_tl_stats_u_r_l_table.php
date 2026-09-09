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
        Schema::create('tl_stats_u_r_l', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_71e8619d8f0b16a25462f301');
            $table->index('account_id', 'ix_77fca02b69d61ff5c80fd8c7');
        });
        Schema::create('tl_stats_u_r_l_stats_u_r_l', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_u_r_l')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_660089f915fa39f139d7b968');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_u_r_l_stats_u_r_l');
        Schema::dropIfExists('tl_stats_u_r_l');
    }
};
