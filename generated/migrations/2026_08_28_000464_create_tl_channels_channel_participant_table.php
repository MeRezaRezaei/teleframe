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
        Schema::create('tl_channels_channel_participant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_channels_channel_participant_channel_participant', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channels_channel_participant')->cascadeOnDelete();
            $table->uuid('participant');
            $table->timestamps();
        });
        Schema::create('tl_channels_channel_participant_channel_participant__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_channel_participant_channel_participant')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_952acb7018e3be9be341');
        });
        Schema::create('tl_channels_channel_participant_channel_participant__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_channel_participant_channel_participant')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ab0589cb2a67b20ddf2a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channels_channel_participant_channel_participant__users');
        Schema::dropIfExists('tl_channels_channel_participant_channel_participant__chats');
        Schema::dropIfExists('tl_channels_channel_participant_channel_participant');
        Schema::dropIfExists('tl_channels_channel_participant');
    }
};
