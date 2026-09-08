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
        Schema::create('tl_phone_group_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_phone_group_participants_group_participants', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_group_participants')->cascadeOnDelete();
            $table->integer('count');
            $table->text('next_offset');
            $table->integer('version');
            $table->timestamps();
        });
        Schema::create('tl_phone_group_participants_group_participant_aa4634f2fc16', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_participants_group_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_bb9801fd6bddc7818111');
        });
        Schema::create('tl_phone_group_participants_group_participants__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_participants_group_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0dca57df727b6f70b02a');
        });
        Schema::create('tl_phone_group_participants_group_participants__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_participants_group_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c7412bfb795ef7d7bf8a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_group_participants_group_participants__users');
        Schema::dropIfExists('tl_phone_group_participants_group_participants__chats');
        Schema::dropIfExists('tl_phone_group_participants_group_participant_aa4634f2fc16');
        Schema::dropIfExists('tl_phone_group_participants_group_participants');
        Schema::dropIfExists('tl_phone_group_participants');
    }
};
