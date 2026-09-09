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
        Schema::create('tl_updates_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a84f13c7fb1d3b0c93babf04');
            $table->index('account_id', 'ix_f9a5f5be78efdbdd8ac39c32');
        });
        Schema::create('tl_updates_state_state', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_state')->cascadeOnDelete();
            $table->integer('pts');
            $table->integer('qts');
            $table->integer('date');
            $table->integer('seq');
            $table->integer('unread_count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b43040ccfae8172ef4e7f000');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_updates_state_state');
        Schema::dropIfExists('tl_updates_state');
    }
};
