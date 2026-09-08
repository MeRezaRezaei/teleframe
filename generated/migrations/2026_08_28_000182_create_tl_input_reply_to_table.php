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
        Schema::create('tl_input_reply_to', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_reply_to_input_reply_to_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_reply_to')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('reply_to_msg_id');
            $table->integer('top_msg_id')->nullable();
            $table->uuid('reply_to_peer_id')->nullable();
            $table->text('quote_text')->nullable();
            $table->integer('quote_offset')->nullable();
            $table->uuid('monoforum_peer_id')->nullable();
            $table->integer('todo_item_id')->nullable();
            $table->binary('poll_option')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_reply_to_input_reply_to_message__quote_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_reply_to_input_reply_to_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_60cd54d79dccbf504fb1');
        });
        Schema::create('tl_input_reply_to_input_reply_to_mono_forum', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_reply_to')->cascadeOnDelete();
            $table->uuid('monoforum_peer_id');
            $table->timestamps();
        });
        Schema::create('tl_input_reply_to_input_reply_to_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_reply_to')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('story_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_reply_to_input_reply_to_story');
        Schema::dropIfExists('tl_input_reply_to_input_reply_to_mono_forum');
        Schema::dropIfExists('tl_input_reply_to_input_reply_to_message__quote_entities');
        Schema::dropIfExists('tl_input_reply_to_input_reply_to_message');
        Schema::dropIfExists('tl_input_reply_to');
    }
};
