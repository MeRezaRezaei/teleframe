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
        Schema::create('tl_group_call_participant_video', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_group_call_participant_video_group_call_pa_2d621b7be2a1', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_group_call_participant_video')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('paused')->default(false);
            $table->text('endpoint');
            $table->integer('audio_source')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_group_call_participant_video_group_call_pa_207eebe3eb6c', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_group_call_participant_video_group_call_pa_2d621b7be2a1')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_861330920a4e29d9c067');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_participant_video_group_call_pa_207eebe3eb6c');
        Schema::dropIfExists('tl_group_call_participant_video_group_call_pa_2d621b7be2a1');
        Schema::dropIfExists('tl_group_call_participant_video');
    }
};
