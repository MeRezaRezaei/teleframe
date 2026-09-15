<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — dialogs domain (Task 2 of the reverse-engineering plan).
 *
 * Hand-authored against TL_telegram_v227.tl (Dialog union: dialog /
 * dialogFolder). Unlike the peer entities, a dialog has no telegram-supplied
 * id — its identity IS its peer, so the composite key is
 * (account_id, peer_type, peer_id) and the peer shape is inline on the parent
 * row (1=user 2=chat 3=channel, PeerShapeTool).
 *
 * - Non-flag scalars shared by both ctors (top_message) and the required
 *   dialog / dialogFolder counters are inline with wire-false sentinels
 *   (a dialog row never carries the folder counters and vice-versa).
 * - flags.?true → BOOLEAN NOT NULL DEFAULT FALSE (pinned, unread_mark,
 *   view_forum_as_messages).
 * - Optional/flag-gated facts (pts, draft, folder_id, ttl_period,
 *   notify_settings, folder) → 1:1 child tables keyed
 *   (account_id, peer_type, peer_id); row existence = fact existence.
 *   notify_settings (PeerNotifySettings) and folder (Folder) are flattened
 *   unions; nested NotificationSound objects / the folder photo are deferred
 *   to the flat decomposer write path.
 * - NO FK constraints here — cross-domain wiring is the Task-8 migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createDialogs();
        $this->createDialogChildren();
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_dialogs_folder');
        Schema::dropIfExists('tf_dialogs_ttl_period');
        Schema::dropIfExists('tf_dialogs_folder_id');
        Schema::dropIfExists('tf_dialogs_draft');
        Schema::dropIfExists('tf_dialogs_pts');
        Schema::dropIfExists('tf_dialogs_notify_settings');
        Schema::dropIfExists('tf_dialogs');
    }

    private function createDialogs(): void
    {
        Schema::create('tf_dialogs', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->text('constructor');
            $table->integer('top_message')->default(0);
            $table->integer('read_inbox_max_id')->default(0);
            $table->integer('read_outbox_max_id')->default(0);
            $table->integer('unread_count')->default(0);
            $table->integer('unread_mentions_count')->default(0);
            $table->integer('unread_reactions_count')->default(0);
            $table->integer('unread_poll_votes_count')->default(0);
            $table->integer('unread_muted_peers_count')->default(0);
            $table->integer('unread_unmuted_peers_count')->default(0);
            $table->integer('unread_muted_messages_count')->default(0);
            $table->integer('unread_unmuted_messages_count')->default(0);
            $table->boolean('pinned')->default(false);
            $table->boolean('unread_mark')->default(false);
            $table->boolean('view_forum_as_messages')->default(false);

            $table->primary(['account_id', 'peer_type', 'peer_id']);
        });
    }

    private function createDialogChildren(): void
    {
        // notify_settings — PeerNotifySettings (single ctor), flattened;
        // NotificationSound object fields deferred.
        Schema::create('tf_dialogs_notify_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->text('constructor');
            $table->boolean('show_previews')->default(false);
            $table->boolean('silent')->default(false);
            $table->integer('mute_until')->default(0);
            $table->boolean('stories_muted')->default(false);
            $table->boolean('stories_hide_sender')->default(false);
            $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_pts', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('pts');
            $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        // draft — DraftMessage union (draftMessageEmpty / draftMessage),
        // flattened; reply_to / entities / media / suggested_post / rich_message
        // nested objects deferred to the flat write path.
        Schema::create('tf_dialogs_draft', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->text('constructor');
            $table->boolean('no_webpage')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->text('message')->default('');
            $table->integer('date')->default(0);
            $table->bigInteger('effect')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_folder_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('folder_id');
            $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        Schema::create('tf_dialogs_ttl_period', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('ttl_period');
            $table->primary(['account_id', 'peer_type', 'peer_id']);
        });

        // folder — single-ctor Folder, flattened; the photo object deferred.
        Schema::create('tf_dialogs_folder', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->text('constructor');
            $table->boolean('autofill_new_broadcasts')->default(false);
            $table->boolean('autofill_public_groups')->default(false);
            $table->boolean('autofill_new_correspondents')->default(false);
            $table->integer('id')->default(0);
            $table->text('title')->default('');
            $table->primary(['account_id', 'peer_type', 'peer_id']);
        });
    }
};
