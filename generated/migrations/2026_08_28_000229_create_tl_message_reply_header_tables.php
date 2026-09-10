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
        Schema::create('tl_message_reply_header_message_reply_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('reply_to_scheduled')->default(false);
            $table->boolean('forum_topic')->default(false);
            $table->boolean('quote')->default(false);
            $table->boolean('reply_to_ephemeral')->default(false);
            $table->integer('reply_to_msg_id')->nullable();
            $table->bigInteger('reply_to_peer_id')->nullable();
            $table->index('reply_to_peer_id', 'ix_5199c435e559b920a96dd02f');
            $table->bigInteger('reply_from')->nullable();
            $table->index('reply_from', 'ix_702ae531f854038e2a7fb07e');
            $table->bigInteger('reply_media')->nullable();
            $table->index('reply_media', 'ix_bf5553b9b9bbe84433473311');
            $table->integer('reply_to_top_id')->nullable();
            $table->text('quote_text')->nullable();
            $table->integer('quote_offset')->nullable();
            $table->integer('todo_item_id')->nullable();
            $table->binary('poll_option')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5619c5de6282043ebd8c7ca5');
            $table->index('account_id', 'ix_9009888f97db0e52840a401e');
        });
        Schema::create('tl_message_reply_header_message_reply_header__1793afabc836', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_reply_header_message_reply_header', 'id', 'fk_dd2ed4bd16b08520a4af2587')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_43f534b01d6eca1fbbaa');
            $table->index('account_id', 'ix_fc6af486cc7cc3ace9e7808d');
        });
        Schema::create('tl_message_reply_header_message_reply_story_header', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_497f5a2a6bd65aa28a10955b');
            $table->integer('story_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a62d54e720a0369646b695d1');
            $table->index('account_id', 'ix_8242391e4f2aff228322de09');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_reply_header_message_reply_story_header');
        Schema::dropIfExists('tl_message_reply_header_message_reply_header__1793afabc836');
        Schema::dropIfExists('tl_message_reply_header_message_reply_header');
    }
};
