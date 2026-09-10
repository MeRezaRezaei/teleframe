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
        Schema::create('tl_stats_group_top_poster_stats_group_top_poster', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_de00cfa7854855103b1a0d2b');
            $table->integer('messages')->nullable();
            $table->integer('avg_chars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0da14372ecab01b8990d4096');
            $table->index('account_id', 'ix_4303b5cce09e4d498afc1e5c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_group_top_poster_stats_group_top_poster');
    }
};
