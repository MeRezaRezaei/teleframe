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
        Schema::create('tl_message_range', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_650a09dd083d307697782389');
            $table->index('account_id', 'ix_c563c6f16d3550f102e5f36b');
        });
        Schema::create('tl_message_range_message_range', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_range')->cascadeOnDelete();
            $table->integer('min_id');
            $table->integer('max_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_90f5df29936ce0da8ca25ae6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_range_message_range');
        Schema::dropIfExists('tl_message_range');
    }
};
