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
        Schema::create('tl_phone_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_phone_group_call_group_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_group_call')->cascadeOnDelete();
            $table->uuid('call');
            $table->text('participants_next_offset');
            $table->timestamps();
        });
        Schema::create('tl_phone_group_call_group_call__participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_call_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_eeaf741b291bf7667c2f');
        });
        Schema::create('tl_phone_group_call_group_call__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_call_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d52653beff16aecfd1f8');
        });
        Schema::create('tl_phone_group_call_group_call__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_call_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_956b6c7c7ee38b4aefdf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_group_call_group_call__users');
        Schema::dropIfExists('tl_phone_group_call_group_call__chats');
        Schema::dropIfExists('tl_phone_group_call_group_call__participants');
        Schema::dropIfExists('tl_phone_group_call_group_call');
        Schema::dropIfExists('tl_phone_group_call');
    }
};
