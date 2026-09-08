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
        Schema::create('tl_message_reply_header', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_reply_header_message_reply_header', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_reply_header')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('reply_to_scheduled')->default(false);
            $table->boolean('forum_topic')->default(false);
            $table->boolean('quote')->default(false);
            $table->boolean('reply_to_ephemeral')->default(false);
            $table->integer('reply_to_msg_id')->nullable();
            $table->uuid('reply_to_peer_id')->nullable();
            $table->uuid('reply_from')->nullable();
            $table->uuid('reply_media')->nullable();
            $table->integer('reply_to_top_id')->nullable();
            $table->text('quote_text')->nullable();
            $table->integer('quote_offset')->nullable();
            $table->integer('todo_item_id')->nullable();
            $table->binary('poll_option')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_reply_header_message_reply_header__1793afabc836', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_reply_header_message_reply_header')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_43f534b01d6eca1fbbaa');
        });
        Schema::create('tl_message_reply_header_message_reply_story_header', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_reply_header')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('story_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_reply_header_message_reply_story_header');
        Schema::dropIfExists('tl_message_reply_header_message_reply_header__1793afabc836');
        Schema::dropIfExists('tl_message_reply_header_message_reply_header');
        Schema::dropIfExists('tl_message_reply_header');
    }
};
