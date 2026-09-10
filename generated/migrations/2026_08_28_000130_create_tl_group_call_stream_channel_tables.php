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
        Schema::create('tl_group_call_stream_channel_group_call_stream_channel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('channel')->nullable();
            $table->integer('scale')->nullable();
            $table->bigInteger('last_timestamp_ms')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b34cbf4973ce5460c79240a8');
            $table->index('account_id', 'ix_5c58eedb3ced8ba69d333a7d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_stream_channel_group_call_stream_channel');
    }
};
