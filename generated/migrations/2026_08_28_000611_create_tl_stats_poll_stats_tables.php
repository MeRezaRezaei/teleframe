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
        Schema::create('tl_stats_poll_stats_poll_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('votes_graph')->nullable();
            $table->index('votes_graph', 'ix_e2fb2f1746bbd3728f1deca6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_284a6de957ef45bec7274dbb');
            $table->index('account_id', 'ix_00a3031b3bd979ac114c82d2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_poll_stats_poll_stats');
    }
};
