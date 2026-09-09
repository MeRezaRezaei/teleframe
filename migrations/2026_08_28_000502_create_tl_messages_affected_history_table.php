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
        Schema::create('tl_messages_affected_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_684ee7ca961a0f8826db981b');
            $table->index('account_id', 'ix_62691b97c18a166374f8dbc1');
        });
        Schema::create('tl_messages_affected_history_affected_history', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_affected_history')->cascadeOnDelete();
            $table->integer('pts');
            $table->integer('pts_count');
            $table->integer('tl_offset');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_70894d1e862fb32af32eae51');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_affected_history_affected_history');
        Schema::dropIfExists('tl_messages_affected_history');
    }
};
