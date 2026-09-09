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
            $table->index('constructor_id', 'ix_6c9f3b24e8b3d0cfc2b81225');
            $table->index('account_id', 'ix_94df60f1c38502efb475fb41');
        });
        Schema::create('tl_channels_channel_participant_channel_participant', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channels_channel_participant')->cascadeOnDelete();
            $table->uuid('participant');
            $table->index('participant', 'ix_f2db847beb0e8f5cb32c4a54');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bb62acc032724756f037f36d');
        });
        Schema::create('tl_channels_channel_participant_channel_participant__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_channel_participant_channel_participant')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_952acb7018e3be9be341');
            $table->index('account_id', 'ix_624273be8b6d52212aa7d3d1');
        });
        Schema::create('tl_channels_channel_participant_channel_participant__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_channel_participant_channel_participant')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ab0589cb2a67b20ddf2a');
            $table->index('account_id', 'ix_2e48f722c292e7ce49c4ece4');
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
