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
        Schema::create('tl_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->boolean('post')->default(false);
            $table->boolean('from_scheduled')->default(false);
            $table->boolean('legacy')->default(false);
            $table->boolean('edit_hide')->default(false);
            $table->boolean('pinned')->default(false);
            $table->boolean('noforwards')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->bigInteger('flags2')->nullable();
            $table->boolean('offline')->default(false);
            $table->boolean('video_processing_pending')->default(false);
            $table->boolean('paid_suggested_post_stars')->default(false);
            $table->boolean('paid_suggested_post_ton')->default(false);
            $table->integer('tl_id');
            $table->uuid('from_id')->nullable();
            $table->integer('from_boosts_applied')->nullable();
            $table->text('from_rank')->nullable();
            $table->uuid('peer_id');
            $table->uuid('saved_peer_id')->nullable();
            $table->uuid('fwd_from')->nullable();
            $table->bigInteger('via_bot_id')->nullable();
            $table->bigInteger('via_business_bot_id')->nullable();
            $table->uuid('guestchat_via_from')->nullable();
            $table->uuid('reply_to')->nullable();
            $table->integer('date');
            $table->text('message');
            $table->uuid('media')->nullable();
            $table->uuid('reply_markup')->nullable();
            $table->integer('views')->nullable();
            $table->integer('forwards')->nullable();
            $table->uuid('replies')->nullable();
            $table->integer('edit_date')->nullable();
            $table->text('post_author')->nullable();
            $table->bigInteger('grouped_id')->nullable();
            $table->uuid('reactions')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->integer('quick_reply_shortcut_id')->nullable();
            $table->bigInteger('effect')->nullable();
            $table->uuid('factcheck')->nullable();
            $table->integer('report_delivery_until_date')->nullable();
            $table->bigInteger('paid_message_stars')->nullable();
            $table->uuid('suggested_post')->nullable();
            $table->integer('schedule_repeat_period')->nullable();
            $table->text('summary_from_language')->nullable();
            $table->uuid('rich_message')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_12c0b220c53ed6b87b94');
        });
        Schema::create('tl_message_message__restriction_reason', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_02bd6d1931bd78bf6392');
        });
        Schema::create('tl_message_message_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('tl_id');
            $table->uuid('peer_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_message_service', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('reactions_are_possible')->default(false);
            $table->boolean('silent')->default(false);
            $table->boolean('post')->default(false);
            $table->boolean('legacy')->default(false);
            $table->integer('tl_id');
            $table->uuid('from_id')->nullable();
            $table->uuid('peer_id');
            $table->uuid('saved_peer_id')->nullable();
            $table->uuid('reply_to')->nullable();
            $table->integer('date');
            $table->uuid('action');
            $table->uuid('reactions')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_message_service');
        Schema::dropIfExists('tl_message_message_empty');
        Schema::dropIfExists('tl_message_message__restriction_reason');
        Schema::dropIfExists('tl_message_message__entities');
        Schema::dropIfExists('tl_message_message');
        Schema::dropIfExists('tl_message');
    }
};
