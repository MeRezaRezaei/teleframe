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
        Schema::create('tl_msgs_ack', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_msgs_ack_msgs_ack', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msgs_ack')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_msgs_ack_msgs_ack__msg_ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_msgs_ack_msgs_ack')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8b61e8e984d394cb3012');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msgs_ack_msgs_ack__msg_ids');
        Schema::dropIfExists('tl_msgs_ack_msgs_ack');
        Schema::dropIfExists('tl_msgs_ack');
    }
};
