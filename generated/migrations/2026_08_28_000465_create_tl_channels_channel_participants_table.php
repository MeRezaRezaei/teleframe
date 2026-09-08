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
        Schema::create('tl_channels_channel_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_channels_channel_participants_channel_participants', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channels_channel_participants')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_channels_channel_participants_channel_part_6b6c9e490b25', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_channel_participants_channel_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d271735286acefa7fcc0');
        });
        Schema::create('tl_channels_channel_participants_channel_part_10e2c32cd676', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_channel_participants_channel_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4a95655594ac623b633e');
        });
        Schema::create('tl_channels_channel_participants_channel_part_c16b51bee12a', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_channel_participants_channel_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_bc5ad0d230cd978d0215');
        });
        Schema::create('tl_channels_channel_participants_channel_part_453012fa781f', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channels_channel_participants')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channels_channel_participants_channel_part_453012fa781f');
        Schema::dropIfExists('tl_channels_channel_participants_channel_part_c16b51bee12a');
        Schema::dropIfExists('tl_channels_channel_participants_channel_part_10e2c32cd676');
        Schema::dropIfExists('tl_channels_channel_participants_channel_part_6b6c9e490b25');
        Schema::dropIfExists('tl_channels_channel_participants_channel_participants');
        Schema::dropIfExists('tl_channels_channel_participants');
    }
};
