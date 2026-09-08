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
            $table->index('constructor_id');
            $table->index('account_id');
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
            $table->uuid('peer');
            $table->text('title');
            $table->integer('icon_color');
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->integer('top_message');
            $table->integer('read_inbox_max_id');
            $table->integer('read_outbox_max_id');
            $table->integer('unread_count');
            $table->integer('unread_mentions_count');
            $table->integer('unread_reactions_count');
            $table->integer('unread_poll_votes_count');
            $table->uuid('from_id');
            $table->uuid('notify_settings');
            $table->uuid('draft')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_forum_topic_forum_topic_deleted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_forum_topic')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_forum_topic_forum_topic_deleted');
        Schema::dropIfExists('tl_forum_topic_forum_topic');
        Schema::dropIfExists('tl_forum_topic');
    }
};
