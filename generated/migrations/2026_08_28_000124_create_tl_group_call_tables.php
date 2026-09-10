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
        Schema::create('tl_group_call_group_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
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
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->integer('participants_count')->nullable();
            $table->text('title')->nullable();
            $table->integer('stream_dc_id')->nullable();
            $table->integer('record_start_date')->nullable();
            $table->integer('schedule_date')->nullable();
            $table->integer('unmuted_video_count')->nullable();
            $table->integer('unmuted_video_limit')->nullable();
            $table->integer('version')->nullable();
            $table->text('invite_link')->nullable();
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->bigInteger('default_send_as')->nullable();
            $table->index('default_send_as', 'ix_e3c7d4bc48bc8a4e9bf8b2b8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_181644ce16c7ba7247103b55');
            $table->index('account_id', 'ix_4022b0026f5aeaa6c0a4b50a');
        });
        Schema::create('tl_group_call_group_call_discarded', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->integer('duration')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_78af1dff09f7f227a7a88edf');
            $table->index('account_id', 'ix_f933787f098f0429159ab53f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_group_call_discarded');
        Schema::dropIfExists('tl_group_call_group_call');
    }
};
