<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror misc domain — Task 7.
 *
 * Covers: folders, saved_dialogs, themes, quick_replies, todo_items,
 * todo_lists, encrypted_chats, group_calls, phone_calls, admin_log_events.
 *
 * Account FK (→ telegram_accounts) is NOT declared here — cross-domain
 * wiring belongs to the 299999 merge migration (Task 8).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── tf_folders ────────────────────────────────────────────
        Schema::create('tf_folders', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('title');
            $table->boolean('autofill_new_broadcasts')->default(false);
            $table->boolean('autofill_public_groups')->default(false);
            $table->boolean('autofill_new_correspondents')->default(false);

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_folders_photo', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->string('constructor', 64)->default('');
            $table->boolean('has_video')->default(false);
            $table->bigInteger('photo_id')->default(0);
            $table->text('stripped_thumb')->default('');
            $table->integer('dc_id')->default(0);

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_folders')
                ->onDelete('cascade');
        });

        // ── tf_saved_dialogs ──────────────────────────────────────
        Schema::create('tf_saved_dialogs', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id');
            $table->integer('top_message');
            $table->boolean('pinned')->default(false);

            $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        // ── tf_themes ─────────────────────────────────────────────
        Schema::create('tf_themes', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('access_hash');
            $table->text('slug');
            $table->text('title');
            $table->boolean('creator')->default(false);
            $table->boolean('default')->default(false);
            $table->boolean('for_chat')->default(false);

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_themes_document', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('document_id');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_themes')
                ->onDelete('cascade');
        });

        Schema::create('tf_themes_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->boolean('message_colors_animated')->default(false);
            $table->integer('accent_color');
            $table->integer('outbox_accent_color')->default(0);

            $table->primary(['account_id', 'id', 'position']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_themes')
                ->onDelete('cascade');
        });

        Schema::create('tf_themes_emoticon', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('emoticon');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_themes')
                ->onDelete('cascade');
        });

        Schema::create('tf_themes_installs_count', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('installs_count');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_themes')
                ->onDelete('cascade');
        });

        // ── tf_quick_replies ──────────────────────────────────────
        Schema::create('tf_quick_replies', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('shortcut_id');
            $table->text('shortcut');
            $table->integer('top_message');
            $table->integer('count');

            $table->primary(['account_id', 'shortcut_id']);
        });

        // ── tf_todo_items ─────────────────────────────────────────
        Schema::create('tf_todo_items', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_todo_items_title', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('title');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_todo_items')
                ->onDelete('cascade');
        });

        // ── tf_todo_lists ─────────────────────────────────────────
        Schema::create('tf_todo_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('todo_list_id');
            $table->boolean('others_can_append')->default(false);
            $table->boolean('others_can_complete')->default(false);

            $table->primary(['account_id', 'todo_list_id']);
        });

        Schema::create('tf_todo_lists_title', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('todo_list_id');
            $table->text('title');

            $table->primary(['account_id', 'todo_list_id']);
            $table->foreign(['account_id', 'todo_list_id'])
                ->references(['account_id', 'todo_list_id'])
                ->on('tf_todo_lists')
                ->onDelete('cascade');
        });

        Schema::create('tf_todo_lists_list', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('todo_list_id');
            $table->smallInteger('position');
            $table->integer('id');
            $table->text('title');

            $table->primary(['account_id', 'todo_list_id', 'position']);
            $table->foreign(['account_id', 'todo_list_id'])
                ->references(['account_id', 'todo_list_id'])
                ->on('tf_todo_lists')
                ->onDelete('cascade');
        });

        // ── tf_encrypted_chats ────────────────────────────────────
        Schema::create('tf_encrypted_chats', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->integer('id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->bigInteger('participant_id');

            $table->primary(['account_id', 'id']);
        });

        // ── tf_group_calls ────────────────────────────────────────
        Schema::create('tf_group_calls', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->bigInteger('id');
            $table->bigInteger('access_hash');
            $table->integer('duration');

            $table->primary(['account_id', 'id']);
        });

        // ── tf_phone_calls ────────────────────────────────────────
        Schema::create('tf_phone_calls', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->bigInteger('id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->bigInteger('participant_id');
            $table->boolean('video')->default(false);

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_phone_calls_protocol', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->boolean('udp_p2p')->default(false);
            $table->boolean('udp_reflector')->default(false);
            $table->integer('min_layer');
            $table->integer('max_layer');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_phone_calls')
                ->onDelete('cascade');
        });

        Schema::create('tf_phone_calls_receive_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('receive_date');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_phone_calls')
                ->onDelete('cascade');
        });

        // ── tf_admin_log_events ───────────────────────────────────
        Schema::create('tf_admin_log_events', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('date');
            $table->bigInteger('user_id');

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->string('constructor', 64);
            $table->text('prev_value')->default('');
            $table->text('new_value')->default('');
            $table->boolean('join_muted')->default(false);
            $table->boolean('via_chatlist')->default(false);
            $table->bigInteger('approved_by')->default(0);
            $table->bigInteger('user_id')->default(0);
            $table->text('prev_rank')->default('');
            $table->text('new_rank')->default('');

            $table->primary(['account_id', 'id']);
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_admin_log_events')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_admin_log_events_action');
        Schema::dropIfExists('tf_admin_log_events');
        Schema::dropIfExists('tf_phone_calls_receive_date');
        Schema::dropIfExists('tf_phone_calls_protocol');
        Schema::dropIfExists('tf_phone_calls');
        Schema::dropIfExists('tf_group_calls');
        Schema::dropIfExists('tf_encrypted_chats');
        Schema::dropIfExists('tf_todo_lists_list');
        Schema::dropIfExists('tf_todo_lists_title');
        Schema::dropIfExists('tf_todo_lists');
        Schema::dropIfExists('tf_todo_items_title');
        Schema::dropIfExists('tf_todo_items');
        Schema::dropIfExists('tf_quick_replies');
        Schema::dropIfExists('tf_themes_installs_count');
        Schema::dropIfExists('tf_themes_emoticon');
        Schema::dropIfExists('tf_themes_settings');
        Schema::dropIfExists('tf_themes_document');
        Schema::dropIfExists('tf_themes');
        Schema::dropIfExists('tf_saved_dialogs');
        Schema::dropIfExists('tf_folders_photo');
        Schema::dropIfExists('tf_folders');
    }
};
