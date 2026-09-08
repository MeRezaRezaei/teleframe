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
        Schema::create('tl_group_call_stream_channel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_group_call_stream_channel_group_call_stream_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_group_call_stream_channel')->cascadeOnDelete();
            $table->integer('channel');
            $table->integer('scale');
            $table->bigInteger('last_timestamp_ms');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_stream_channel_group_call_stream_channel');
        Schema::dropIfExists('tl_group_call_stream_channel');
    }
};
