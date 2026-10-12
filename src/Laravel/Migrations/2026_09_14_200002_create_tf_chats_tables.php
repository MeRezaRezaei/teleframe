<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * NF5 mirror — chats domain (Task 2 of the reverse-engineering plan).
 *
 * Hand-authored against TL_telegram_v227.tl (Chat union). The
 * channel / channelForbidden ctors resolve to Chat but are routed to
 * tf_channels (Naming::CTOR_DOMAIN_OVERRIDES) — so this table is the peer FK
 * target for peer_type 2 (PeerShapeTool::PEER_CHAT) and holds chatEmpty /
 * chat / chatForbidden.
 *
 * - id is a signed BIGINT: telegram chat ids are negative.
 * - Non-flag scalars (title, participants_count, date, version) are inline;
 *   flags.?true → BOOLEAN NOT NULL DEFAULT FALSE (catalog "bools" list).
 * - photo is a REQUIRED object in the chat ctor but an object union has no
 *   inline representation → 1:1 child table tf_chats_photo (constructor-
 *   discriminated, flattened with wire-false sentinels).
 * - Optional/flag-gated object facts (migrated_to, admin_rights,
 *   default_banned_rights) → 1:1 child tables; row existence = fact
 *   existence. migrated_to is an InputChannel union whose inputChannelFromMessage
 *   variant carries a Peer → inline peer_type/peer_id pair.
 * - NO FK constraints here — cross-domain wiring is the Task-8 migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createChats();
        $this->createChatChildren();
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_chats_default_banned_rights');
        Schema::dropIfExists('tf_chats_admin_rights');
        Schema::dropIfExists('tf_chats_migrated_to');
        Schema::dropIfExists('tf_chats_photo');
        Schema::dropIfExists('tf_chats');
    }

    private function createChats(): void
    {
        Schema::create('tf_chats', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->text('title')->default(DB::raw("('')"));
            $table->integer('participants_count')->default(0);
            $table->integer('date')->default(0);
            $table->integer('version')->default(0);
            $table->boolean('creator')->default(false);
            $table->boolean('left')->default(false);
            $table->boolean('deactivated')->default(false);
            $table->boolean('call_active')->default(false);
            $table->boolean('call_not_empty')->default(false);
            $table->boolean('noforwards')->default(false);

            $table->primary(['account_id', 'id']);
        });
    }

    private function createChatChildren(): void
    {
        // photo — ChatPhoto union (chatPhotoEmpty / chatPhoto).
        Schema::create('tf_chats_photo', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('has_video')->default(false);
            $table->bigInteger('photo_id')->default(0);
            $table->text('stripped_thumb')->default(DB::raw("('')"));
            $table->integer('dc_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // migrated_to — InputChannel union (inputChannelEmpty / inputChannel /
        // inputChannelFromMessage). The FromMessage variant's peer expands to
        // a canonical peer pair; its msg_id is flat.
        Schema::create('tf_chats_migrated_to', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->bigInteger('channel_id')->default(0);
            $table->bigInteger('access_hash')->default(0);
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('msg_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // admin_rights — single-ctor ChatAdminRights, all flags.?true bools.
        Schema::create('tf_chats_admin_rights', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('change_info')->default(false);
            $table->boolean('post_messages')->default(false);
            $table->boolean('edit_messages')->default(false);
            $table->boolean('delete_messages')->default(false);
            $table->boolean('ban_users')->default(false);
            $table->boolean('invite_users')->default(false);
            $table->boolean('pin_messages')->default(false);
            $table->boolean('add_admins')->default(false);
            $table->boolean('anonymous')->default(false);
            $table->boolean('manage_call')->default(false);
            $table->boolean('other')->default(false);
            $table->boolean('manage_topics')->default(false);
            $table->boolean('post_stories')->default(false);
            $table->boolean('edit_stories')->default(false);
            $table->boolean('delete_stories')->default(false);
            $table->boolean('manage_direct_messages')->default(false);
            $table->boolean('manage_ranks')->default(false);
            $table->primary(['account_id', 'id']);
        });

        // default_banned_rights — single-ctor ChatBannedRights: bools + until_date.
        Schema::create('tf_chats_default_banned_rights', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('view_messages')->default(false);
            $table->boolean('send_messages')->default(false);
            $table->boolean('send_media')->default(false);
            $table->boolean('send_stickers')->default(false);
            $table->boolean('send_gifs')->default(false);
            $table->boolean('send_games')->default(false);
            $table->boolean('send_inline')->default(false);
            $table->boolean('embed_links')->default(false);
            $table->boolean('send_polls')->default(false);
            $table->boolean('change_info')->default(false);
            $table->boolean('invite_users')->default(false);
            $table->boolean('pin_messages')->default(false);
            $table->boolean('manage_topics')->default(false);
            $table->boolean('send_photos')->default(false);
            $table->boolean('send_videos')->default(false);
            $table->boolean('send_roundvideos')->default(false);
            $table->boolean('send_audios')->default(false);
            $table->boolean('send_voices')->default(false);
            $table->boolean('send_docs')->default(false);
            $table->boolean('send_plain')->default(false);
            $table->boolean('edit_rank')->default(false);
            $table->boolean('send_reactions')->default(false);
            $table->integer('until_date')->default(0);
            $table->primary(['account_id', 'id']);
        });
    }
};
