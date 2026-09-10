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
        Schema::create('tl_forum_topic_forum_topic', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('my')->default(false);
            $table->boolean('closed')->default(false);
            $table->boolean('pinned')->default(false);
            $table->boolean('short')->default(false);
            $table->boolean('hidden')->default(false);
            $table->boolean('title_missing')->default(false);
            $table->integer('tl_id')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_bb339dbc8be33d4731e6a2d6');
            $table->text('title')->nullable();
            $table->integer('icon_color')->nullable();
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->index('icon_emoji_id', 'ix_686bac406d87993a858488db');
            $table->integer('top_message')->nullable();
            $table->integer('read_inbox_max_id')->nullable();
            $table->integer('read_outbox_max_id')->nullable();
            $table->integer('unread_count')->nullable();
            $table->integer('unread_mentions_count')->nullable();
            $table->integer('unread_reactions_count')->nullable();
            $table->integer('unread_poll_votes_count')->nullable();
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_ca4a65a7ea21850f441d50af');
            $table->bigInteger('notify_settings')->nullable();
            $table->index('notify_settings', 'ix_15285bc1dcb60180291538f4');
            $table->bigInteger('draft')->nullable();
            $table->index('draft', 'ix_d5461dd22fdbf77079086b97');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_710c2bf6c73289d0af967059');
            $table->index('account_id', 'ix_dd4360afa8d2256960e700a2');
            $table->unique(['peer', 'account_id'], 'ux_705864cb0d6eebe32c49');
        });
        Schema::create('tl_forum_topic_forum_topic_deleted', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_add6bb09a924e47b1f6aa08b');
            $table->index('account_id', 'ix_19203361fcc00fc362abc7d5');
            $table->unique(['account_id'], 'ux_8bb151e588c53fd5cfe6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_forum_topic_forum_topic_deleted');
        Schema::dropIfExists('tl_forum_topic_forum_topic');
    }
};
