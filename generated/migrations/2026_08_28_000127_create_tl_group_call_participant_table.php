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
        Schema::create('tl_group_call_participant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_group_call_participant_group_call_participant', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_group_call_participant')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('muted')->default(false);
            $table->boolean('left')->default(false);
            $table->boolean('can_self_unmute')->default(false);
            $table->boolean('just_joined')->default(false);
            $table->boolean('versioned')->default(false);
            $table->boolean('min')->default(false);
            $table->boolean('muted_by_you')->default(false);
            $table->boolean('volume_by_admin')->default(false);
            $table->boolean('self')->default(false);
            $table->boolean('video_joined')->default(false);
            $table->uuid('peer');
            $table->integer('date');
            $table->integer('active_date')->nullable();
            $table->integer('source');
            $table->integer('volume')->nullable();
            $table->text('about')->nullable();
            $table->bigInteger('raise_hand_rating')->nullable();
            $table->uuid('video')->nullable();
            $table->uuid('presentation')->nullable();
            $table->bigInteger('paid_stars_total')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_participant_group_call_participant');
        Schema::dropIfExists('tl_group_call_participant');
    }
};
