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
        Schema::create('tl_group_call_participant_video_source_group', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_group_call_participant_video_source_group__d4c024526fb4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_group_call_participant_video_source_group')->cascadeOnDelete();
            $table->text('semantics');
            $table->timestamps();
        });
        Schema::create('tl_group_call_participant_video_source_group__3aa33ccb5d6b', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_group_call_participant_video_source_group__d4c024526fb4')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_90f25ab9e227d8da2b18');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_participant_video_source_group__3aa33ccb5d6b');
        Schema::dropIfExists('tl_group_call_participant_video_source_group__d4c024526fb4');
        Schema::dropIfExists('tl_group_call_participant_video_source_group');
    }
};
