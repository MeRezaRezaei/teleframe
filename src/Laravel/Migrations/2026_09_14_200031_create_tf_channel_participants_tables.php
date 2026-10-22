<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hand-authored NF5 mirror for the UPDATES domain — secondary updates facts
 * (owner verbatim ruling: generation is banned; derived by hand from
 * schema/sources/TL_telegram_v227.tl).
 *
 * tf_channel_participants — ChannelParticipant union. PK
 *                           (account_id, channel_id, user_id); 'user_id'
 *                           also carries the peer's user id for the
 *                           peer-carrying ctors (channelParticipantBanned /
 *                           Left keep the exact pair in
 *                           tf_channel_participants_peer). 'date' is 0 for
 *                           channelParticipantCreator (no date field).
 * tf_channel_updates      — updates.ChannelDifference union: one row per
 *                           channel-diff response, discriminated by
 *                           'constructor' (channelDifferenceEmpty /
 *                           channelDifferenceTooLong / channelDifference),
 *                           'is_final' mirrors the flags.final bit.
 * tf_update_differences   — updates.Difference union (differenceEmpty /
 *                           difference / differenceSlice /
 *                           differenceTooLong): the few scalar sync facts;
 *                           vector members (new_messages, other_updates,
 *                           chats, users) are mirrored by their OWN domain
 *                           tables — this row records sync state only.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createChannelParticipants();
        $this->createChannelUpdates();
        $this->createDifferences();
    }

    public function down(): void
    {
        foreach ([
            'tf_update_differences_state',
            'tf_update_differences_pts',
            'tf_update_differences_seq',
            'tf_update_differences_date',
        ] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_update_differences');

        foreach (['tf_channel_updates_dialog', 'tf_channel_updates_timeout', 'tf_channel_updates_pts'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_channel_updates');

        foreach ([
            'tf_channel_participants_rank',
            'tf_channel_participants_subscription_until_date',
            'tf_channel_participants_kicked_by',
            'tf_channel_participants_promoted_by',
            'tf_channel_participants_inviter',
            'tf_channel_participants_peer',
        ] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_channel_participants');
    }

    private function createChannelParticipants(): void
    {
        Schema::create('tf_channel_participants', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->string('constructor', 64);
            $table->integer('date')->default(0);
            // Flag bits common across the union (zero/default outside their ctor).
            $table->boolean('via_request')->default(false);
            $table->boolean('is_self')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('is_left')->default(false);
            $table->primary(['account_id', 'channel_id', 'user_id']);
            $table->foreign('account_id')->references('id')->on('telegram_accounts')->cascadeOnDelete();
        });

        // channelParticipantBanned/Left: the exact peer pair (usually the
        // purposeful user), beyond the user_id convenience column above.
        Schema::create('tf_channel_participants_peer', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->primary(['account_id', 'channel_id', 'user_id']);
            self::participantLink($table);
        });
        // channelParticipantSelf/Admin: inviter_id.
        Schema::create('tf_channel_participants_inviter', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->bigInteger('inviter_id');
            $table->primary(['account_id', 'channel_id', 'user_id']);
            self::participantLink($table);
        });
        // channelParticipantAdmin: promoted_by.
        Schema::create('tf_channel_participants_promoted_by', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->bigInteger('promoted_by');
            $table->primary(['account_id', 'channel_id', 'user_id']);
            self::participantLink($table);
        });
        // channelParticipantBanned: kicked_by.
        Schema::create('tf_channel_participants_kicked_by', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->bigInteger('kicked_by');
            $table->primary(['account_id', 'channel_id', 'user_id']);
            self::participantLink($table);
        });
        // channelParticipant/Admin/Self: subscription_until_date (flags.0/1).
        Schema::create('tf_channel_participants_subscription_until_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->integer('subscription_until_date');
            $table->primary(['account_id', 'channel_id', 'user_id']);
            self::participantLink($table);
        });
        // channelParticipant/Admin/Creator/Banned: rank (flags.2).
        Schema::create('tf_channel_participants_rank', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->text('rank');
            $table->primary(['account_id', 'channel_id', 'user_id']);
            self::participantLink($table);
        });
        // admin_rights / banned_rights (ChatAdminRights / ChatBannedRights)
        // deferred: their home is the identity domain mirror
        // (tf_chats_admin_rights / tf_chats_default_banned_rights).
    }

    private function createChannelUpdates(): void
    {
        Schema::create('tf_channel_updates', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->smallInteger('position');
            $table->string('constructor', 64);
            $table->boolean('is_final')->default(false);
            $table->primary(['account_id', 'channel_id', 'position']);
            $table->foreign('account_id')->references('id')->on('telegram_accounts')->cascadeOnDelete();
        });

        Schema::create('tf_channel_updates_pts', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->smallInteger('position');
            $table->integer('pts');
            $table->primary(['account_id', 'channel_id', 'position']);
            self::channelUpdateLink($table);
        });
        // channelDifferenceTooLong/channelDifference: flags.timeout.
        Schema::create('tf_channel_updates_timeout', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->smallInteger('position');
            $table->integer('timeout');
            $table->primary(['account_id', 'channel_id', 'position']);
            self::channelUpdateLink($table);
        });
        // channelDifferenceTooLong: the pinned dialog (peer ref + top_message).
        Schema::create('tf_channel_updates_dialog', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->smallInteger('position');
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('top_message');
            $table->primary(['account_id', 'channel_id', 'position']);
            self::channelUpdateLink($table);
        });
    }

    private function createDifferences(): void
    {
        Schema::create('tf_update_differences', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->smallInteger('position');
            $table->string('constructor', 64);
            $table->primary(['account_id', 'position']);
            $table->foreign('account_id')->references('id')->on('telegram_accounts')->cascadeOnDelete();
        });

        // differenceEmpty: date + seq.
        Schema::create('tf_update_differences_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->smallInteger('position');
            $table->integer('date');
            $table->primary(['account_id', 'position']);
            self::differenceLink($table);
        });
        Schema::create('tf_update_differences_seq', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->smallInteger('position');
            $table->integer('seq');
            $table->primary(['account_id', 'position']);
            self::differenceLink($table);
        });
        // differenceTooLong: pts.
        Schema::create('tf_update_differences_pts', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->smallInteger('position');
            $table->integer('pts');
            $table->primary(['account_id', 'position']);
            self::differenceLink($table);
        });
        // difference / differenceSlice: the embedded updates.State snapshot
        // (state / intermediate_state).
        Schema::create('tf_update_differences_state', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->smallInteger('position');
            $table->integer('pts');
            $table->integer('qts');
            $table->integer('date');
            $table->integer('seq');
            $table->integer('unread_count');
            $table->primary(['account_id', 'position']);
            self::differenceLink($table);
        });
    }

    private static function participantLink(Blueprint $table): void
    {
        $table->foreign(['account_id', 'channel_id', 'user_id'])
            ->references(['account_id', 'channel_id', 'user_id'])
            ->on('tf_channel_participants')
            ->cascadeOnDelete()
            ->name('fk_participant_link_'.$table->getTable());
    }

    private static function channelUpdateLink(Blueprint $table): void
    {
        $table->foreign(['account_id', 'channel_id', 'position'])
            ->references(['account_id', 'channel_id', 'position'])
            ->on('tf_channel_updates')
            ->cascadeOnDelete()
            ->name('fk_channel_update_'.$table->getTable());
    }

    private static function differenceLink(Blueprint $table): void
    {
        $table->foreign(['account_id', 'position'])
            ->references(['account_id', 'position'])
            ->on('tf_update_differences')
            ->cascadeOnDelete()
            ->name('fk_diff_link_'.$table->getTable());
    }
};
