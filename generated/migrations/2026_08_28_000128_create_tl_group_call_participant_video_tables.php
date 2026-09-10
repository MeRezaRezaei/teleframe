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
        Schema::create('tl_group_call_participant_video_group_call_pa_2d621b7be2a1', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('paused')->default(false);
            $table->text('endpoint')->nullable();
            $table->integer('audio_source')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1c5004b5d267d3595ec83145');
            $table->index('account_id', 'ix_255ab7547bb72dbe91271b9b');
        });
        Schema::create('tl_group_call_participant_video_group_call_pa_207eebe3eb6c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_group_call_participant_video_group_call_pa_2d621b7be2a1', 'id', 'fk_291f09cdaa51e0156728a905')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_861330920a4e29d9c067');
            $table->index('account_id', 'ix_93c1471365eeea5a1442a099');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_participant_video_group_call_pa_207eebe3eb6c');
        Schema::dropIfExists('tl_group_call_participant_video_group_call_pa_2d621b7be2a1');
    }
};
