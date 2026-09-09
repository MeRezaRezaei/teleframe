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
        Schema::create('tl_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8907b4b506973a69d5c45ae9');
            $table->index('account_id', 'ix_5a0d730ebe890437bce3d82f');
        });
        Schema::create('tl_group_call_group_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_group_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('join_muted')->default(false);
            $table->boolean('can_change_join_muted')->default(false);
            $table->boolean('join_date_asc')->default(false);
            $table->boolean('schedule_start_subscribed')->default(false);
            $table->boolean('can_start_video')->default(false);
            $table->boolean('record_video_active')->default(false);
            $table->boolean('rtmp_stream')->default(false);
            $table->boolean('listeners_hidden')->default(false);
            $table->boolean('conference')->default(false);
            $table->boolean('creator')->default(false);
            $table->boolean('messages_enabled')->default(false);
            $table->boolean('can_change_messages_enabled')->default(false);
            $table->boolean('min')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('participants_count');
            $table->text('title')->nullable();
            $table->integer('stream_dc_id')->nullable();
            $table->integer('record_start_date')->nullable();
            $table->integer('schedule_date')->nullable();
            $table->integer('unmuted_video_count')->nullable();
            $table->integer('unmuted_video_limit');
            $table->integer('version');
            $table->text('invite_link')->nullable();
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->bigInteger('default_send_as')->nullable();
            $table->index('default_send_as', 'ix_e3c7d4bc48bc8a4e9bf8b2b8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4022b0026f5aeaa6c0a4b50a');
            $table->unique(['account_id', 'default_send_as', 'tl_id'], 'ux_1fbf1b49e24501048e71');
        });
        Schema::create('tl_group_call_group_call_discarded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_group_call')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('duration');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f933787f098f0429159ab53f');
            $table->unique(['account_id', 'tl_id'], 'ux_76d81dce95ad24d7a79a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_group_call_discarded');
        Schema::dropIfExists('tl_group_call_group_call');
        Schema::dropIfExists('tl_group_call');
    }
};
