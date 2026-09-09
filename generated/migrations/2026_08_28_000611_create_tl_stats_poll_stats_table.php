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
        Schema::create('tl_stats_poll_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3da9519fc4e9248cbb3e4e36');
            $table->index('account_id', 'ix_a6b1302b2c4ff5a968b13377');
        });
        Schema::create('tl_stats_poll_stats_poll_stats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_poll_stats')->cascadeOnDelete();
            $table->uuid('votes_graph');
            $table->index('votes_graph', 'ix_e2fb2f1746bbd3728f1deca6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_00a3031b3bd979ac114c82d2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_poll_stats_poll_stats');
        Schema::dropIfExists('tl_stats_poll_stats');
    }
};
