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
        Schema::create('tl_dialog', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_dialog_dialog', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_dialog')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->boolean('unread_mark')->default(false);
            $table->boolean('view_forum_as_messages')->default(false);
            $table->uuid('peer');
            $table->integer('top_message');
            $table->integer('read_inbox_max_id');
            $table->integer('read_outbox_max_id');
            $table->integer('unread_count');
            $table->integer('unread_mentions_count');
            $table->integer('unread_reactions_count');
            $table->integer('unread_poll_votes_count');
            $table->uuid('notify_settings');
            $table->integer('pts')->nullable();
            $table->uuid('draft')->nullable();
            $table->integer('folder_id')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_dialog_dialog_folder', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_dialog')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->uuid('folder');
            $table->uuid('peer');
            $table->integer('top_message');
            $table->integer('unread_muted_peers_count');
            $table->integer('unread_unmuted_peers_count');
            $table->integer('unread_muted_messages_count');
            $table->integer('unread_unmuted_messages_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_dialog_dialog_folder');
        Schema::dropIfExists('tl_dialog_dialog');
        Schema::dropIfExists('tl_dialog');
    }
};
