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
            $table->index('constructor_id', 'ix_f05e1a53f401af97b2242d04');
            $table->index('account_id', 'ix_5d83fb4345080526832b5264');
        });
        Schema::create('tl_input_reply_to_input_reply_to_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_reply_to')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('reply_to_msg_id');
            $table->integer('top_msg_id')->nullable();
            $table->bigInteger('reply_to_peer_id')->nullable();
            $table->index('reply_to_peer_id', 'ix_f47c8c9ae5581f79b367665a');
            $table->text('quote_text')->nullable();
            $table->integer('quote_offset')->nullable();
            $table->bigInteger('monoforum_peer_id')->nullable();
            $table->index('monoforum_peer_id', 'ix_b2e90d6837fc7b8b44d3cf8f');
            $table->integer('todo_item_id')->nullable();
            $table->binary('poll_option')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_234cf5dde9c69f0232dfe5d5');
        });
        Schema::create('tl_input_reply_to_input_reply_to_message__quote_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_reply_to_input_reply_to_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_60cd54d79dccbf504fb1');
            $table->index('account_id', 'ix_108413da79e545ead04e3f24');
        });
        Schema::create('tl_input_reply_to_input_reply_to_mono_forum', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_reply_to')->cascadeOnDelete();
            $table->bigInteger('monoforum_peer_id');
            $table->index('monoforum_peer_id', 'ix_50bdef1529f58d954d7e358a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c8ad73c4d193c328e04c89ec');
        });
        Schema::create('tl_input_reply_to_input_reply_to_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_reply_to')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_ed4d004b7cecbdcaec6fc088');
            $table->integer('story_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_26007e43cacfc92bd534d83e');
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
