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
        Schema::create('tl_msgs_state_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f6241943b7587f8faf553ac1');
            $table->index('account_id', 'ix_a3f85278ec8e56b1c7321a83');
        });
        Schema::create('tl_msgs_state_info_msgs_state_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msgs_state_info')->cascadeOnDelete();
            $table->bigInteger('req_msg_id');
            $table->index('req_msg_id', 'ix_f1fa51825a82f4f26c6ee1f5');
            $table->text('info');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b4aabbf8e2f08821fb6828ee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msgs_state_info_msgs_state_info');
        Schema::dropIfExists('tl_msgs_state_info');
    }
};
