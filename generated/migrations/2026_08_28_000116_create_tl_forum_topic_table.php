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
        Schema::create('tl_forum_topic', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ca751bcba29f2f143fd09a6e');
            $table->index('account_id', 'ix_63383329227b4bb66cfd8a5d');
        });
        Schema::create('tl_forum_topic_forum_topic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_forum_topic')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('my')->default(false);
            $table->boolean('closed')->default(false);
            $table->boolean('pinned')->default(false);
            $table->boolean('short')->default(false);
            $table->boolean('hidden')->default(false);
            $table->boolean('title_missing')->default(false);
            $table->integer('tl_id');
            $table->integer('date');
            $table->bigInteger('peer');
            $table->index('peer', 'ix_bb339dbc8be33d4731e6a2d6');
            $table->text('title');
            $table->integer('icon_color');
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->index('icon_emoji_id', 'ix_686bac406d87993a858488db');
            $table->integer('top_message');
            $table->integer('read_inbox_max_id');
            $table->integer('read_outbox_max_id');
            $table->integer('unread_count');
            $table->integer('unread_mentions_count');
            $table->integer('unread_reactions_count');
            $table->integer('unread_poll_votes_count');
            $table->bigInteger('from_id');
            $table->index('from_id', 'ix_ca4a65a7ea21850f441d50af');
            $table->uuid('notify_settings');
            $table->index('notify_settings', 'ix_15285bc1dcb60180291538f4');
            $table->uuid('draft')->nullable();
            $table->index('draft', 'ix_d5461dd22fdbf77079086b97');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dd4360afa8d2256960e700a2');
            $table->unique(['account_id', 'from_id', 'tl_id'], 'ux_705864cb0d6eebe32c49');
        });
        Schema::create('tl_forum_topic_forum_topic_deleted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_forum_topic')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_19203361fcc00fc362abc7d5');
            $table->unique(['account_id', 'tl_id'], 'ux_8bb151e588c53fd5cfe6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_forum_topic_forum_topic_deleted');
        Schema::dropIfExists('tl_forum_topic_forum_topic');
        Schema::dropIfExists('tl_forum_topic');
    }
};
