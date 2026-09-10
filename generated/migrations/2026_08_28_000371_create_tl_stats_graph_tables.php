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
        Schema::create('tl_stats_graph_stats_graph', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('json')->nullable();
            $table->index('json', 'ix_6bf8ffbd333dfc42500969cd');
            $table->text('zoom_token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2647a3b48e3170840a1fd942');
            $table->index('account_id', 'ix_de828f782c3507dfd9ed31cb');
        });
        Schema::create('tl_stats_graph_stats_graph_async', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_06e53e1edb1c04ccc6fe52d2');
            $table->index('account_id', 'ix_fe78caa18a92f377937baddf');
        });
        Schema::create('tl_stats_graph_stats_graph_error', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('error')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7331ca3b37e64520764b17c5');
            $table->index('account_id', 'ix_b0cd2068d8244f03e200373e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_graph_stats_graph_error');
        Schema::dropIfExists('tl_stats_graph_stats_graph_async');
        Schema::dropIfExists('tl_stats_graph_stats_graph');
    }
};
