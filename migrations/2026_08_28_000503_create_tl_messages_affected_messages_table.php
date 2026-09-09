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
        Schema::create('tl_messages_affected_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6cb23f7f87c5cd8399071189');
            $table->index('account_id', 'ix_719090f0c79e0440ac31a470');
        });
        Schema::create('tl_messages_affected_messages_affected_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_affected_messages')->cascadeOnDelete();
            $table->integer('pts');
            $table->integer('pts_count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ba48e70a065fcb6db7e040a6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_affected_messages_affected_messages');
        Schema::dropIfExists('tl_messages_affected_messages');
    }
};
