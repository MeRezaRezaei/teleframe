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
        Schema::create('tl_stats_message_stats_message_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('views_graph')->nullable();
            $table->index('views_graph', 'ix_989af242370be61f9220f7d2');
            $table->bigInteger('reactions_by_emotion_graph')->nullable();
            $table->index('reactions_by_emotion_graph', 'ix_99a7c73b8fde527462c01b7c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_14e33bc9c2d01a3e8505cad2');
            $table->index('account_id', 'ix_d42e8332d5e5a054c6cf24e1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_message_stats_message_stats');
    }
};
