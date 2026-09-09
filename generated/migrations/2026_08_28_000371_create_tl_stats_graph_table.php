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
        Schema::create('tl_stats_graph', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2c3d90b7a3a16d38ccec3e10');
            $table->index('account_id', 'ix_96d845aaa1786c68d9671e91');
        });
        Schema::create('tl_stats_graph_stats_graph', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_graph')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('json');
            $table->index('json', 'ix_6bf8ffbd333dfc42500969cd');
            $table->text('zoom_token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_de828f782c3507dfd9ed31cb');
        });
        Schema::create('tl_stats_graph_stats_graph_async', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_graph')->cascadeOnDelete();
            $table->text('token');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fe78caa18a92f377937baddf');
        });
        Schema::create('tl_stats_graph_stats_graph_error', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_graph')->cascadeOnDelete();
            $table->text('error');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b0cd2068d8244f03e200373e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_graph_stats_graph_error');
        Schema::dropIfExists('tl_stats_graph_stats_graph_async');
        Schema::dropIfExists('tl_stats_graph_stats_graph');
        Schema::dropIfExists('tl_stats_graph');
    }
};
