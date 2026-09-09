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
        Schema::create('tl_msgs_all_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a0a41d8d416fad99aad7d4cb');
            $table->index('account_id', 'ix_dc12675837afc401043f0ee1');
        });
        Schema::create('tl_msgs_all_info_msgs_all_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_msgs_all_info')->cascadeOnDelete();
            $table->text('info');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c9f353db4f2d41318da68eea');
        });
        Schema::create('tl_msgs_all_info_msgs_all_info__msg_ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_msgs_all_info_msgs_all_info')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b3048327ad87a1c5f779');
            $table->index('account_id', 'ix_9a448ab01d8a2ce66ce3e3ed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msgs_all_info_msgs_all_info__msg_ids');
        Schema::dropIfExists('tl_msgs_all_info_msgs_all_info');
        Schema::dropIfExists('tl_msgs_all_info');
    }
};
