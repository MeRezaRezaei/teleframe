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
        Schema::create('tl_stats_message_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1d5301ad8d065a6f31a2dd23');
            $table->index('account_id', 'ix_2e1ad045e16403dc33cf1c8a');
        });
        Schema::create('tl_stats_message_stats_message_stats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_message_stats')->cascadeOnDelete();
            $table->uuid('views_graph');
            $table->index('views_graph', 'ix_989af242370be61f9220f7d2');
            $table->uuid('reactions_by_emotion_graph');
            $table->index('reactions_by_emotion_graph', 'ix_99a7c73b8fde527462c01b7c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d42e8332d5e5a054c6cf24e1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_message_stats_message_stats');
        Schema::dropIfExists('tl_stats_message_stats');
    }
};
