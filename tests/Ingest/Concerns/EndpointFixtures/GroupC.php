<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Realistic decoded payloads for the `C` slice of the layer-227 update-constructor
 * universe: every `updateXxx` whose first character after the literal string
 * `update` is `C`.
 *
 * MEASURED COUNT: 21.
 *
 * Command that produced the set — a PARSED constructor map, never a grep, because
 * a text search also matches RPC methods such as `updates.getDifference`:
 *
 *     timeout -k 5 180 php -r 'require "vendor/autoload.php";
 *       $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(__DIR__."/schema/sources/TL_telegram_v227.tl");
 *       $n = []; foreach ($s->types() as $t) { foreach (array_keys((array) $t->constructors()) as $k) { $n[(string)$k] = true; } }
 *       $u = array_values(array_filter(array_keys($n), fn($k) => str_starts_with($k, "update")));
 *       sort($u);
 *       foreach ($u as $k) { if (strtoupper(substr($k,6,1)) === "C") echo $k, "\n"; }'
 *
 * Verbatim output of that command (21 lines, exactly the keys of payloads() below):
 *
 *     updateChannel
 *     updateChannelAvailableMessages
 *     updateChannelMessageForwards
 *     updateChannelMessageViews
 *     updateChannelParticipant
 *     updateChannelReadMessagesContents
 *     updateChannelTooLong
 *     updateChannelUserTyping
 *     updateChannelViewForumAsMessages
 *     updateChannelWebPage
 *     updateChat
 *     updateChatDefaultBannedRights
 *     updateChatParticipant
 *     updateChatParticipantAdd
 *     updateChatParticipantAdmin
 *     updateChatParticipantDelete
 *     updateChatParticipantRank
 *     updateChatParticipants
 *     updateChatUserTyping
 *     updateConfig
 *     updateContactsReset
 *
 * Field lists were read out of the constructor blocks in
 * `schema/sources/TL_telegram_v227.tl` (lines 318-319, 327-328, 338-339, 343-344,
 * 359, 361, 372-374, 377, 391, 395, 398, 402-403, 429, 466), and nested types out
 * of `chatParticipant*` (110-115), `chatInviteExported` (670), `chatBannedRights`
 * (1281), `sendMessageTypingAction` (548), `webPagePending` (652),
 * `channelParticipant*` (766-771).
 *
 * SHAPE CONVENTION — identical to tests/Ingest/Concerns/HasNestedUpdateFixtures.php
 * and to the landed siblings in this directory:
 *  - `_` carries the BARE constructor name on EVERY constructor node, nested ones
 *    included; no `#hex` ids anywhere in a fixture.
 *  - set-flag booleans arrive as EXPLICIT keys beside the `flags` bitmask that
 *    turns them on (`'flags' => (1 << 0), 'send_messages' => true`) — that is what
 *    the decoder actually sees on the wire.
 *  - vectors are PHP lists of constructor arrays; peers are constructor objects
 *    (`['_','peerUser','user_id' => N]`).
 *  - only fields the constructor actually DECLARES appear, and a flags-conditioned
 *    field appears only when its bit IS set in the mask given here, so the decoder
 *    is never asked to read a field the wire would not have sent.
 *  - every value is SYNTHETIC: obviously-fake repeated-digit `access_hash`
 *    integers, no session strings, no credentials, `.env` never read.
 *  - ids are distinct per endpoint so nothing collides in the peer-scoped key
 *    (account_id, peer_type, peer_id, id) — see
 *    tests/Mirror/MessagePeerScopedPrimaryKeyTest.php.
 *
 * SELF-CHECK: `count(self::payloads()) === 21`, measured over the same parsed
 * constructor map; every key has `strtoupper(substr($k, 6, 1)) === 'C'` and every
 * derived `C` constructor is present, so this group owns the whole letter.
 */
trait GroupC
{
    /**
     * The index-6 letters this group owns.
     *
     * @return list<string>
     */
    public static function letters(): array
    {
        return ['C'];
    }

    /**
     * @return array<string, array<string, mixed>> ctor name => a decoded payload
     */
    public static function payloads(): array
    {
        return [
            // channel_id:long — the single unconditional field.
            'updateChannel' => [
                '_' => 'updateChannel',
                'channel_id' => 1737473577,
            ],

            // channel_id:long available_min_id:int — no flags.
            'updateChannelAvailableMessages' => [
                '_' => 'updateChannelAvailableMessages',
                'channel_id' => 1737473577,
                'available_min_id' => 3521,
            ],

            // channel_id:long id:int forwards:int — no flags.
            'updateChannelMessageForwards' => [
                '_' => 'updateChannelMessageForwards',
                'channel_id' => 1737473577,
                'id' => 3531,
                'forwards' => 11,
            ],

            // channel_id:long id:int views:int — no flags.
            'updateChannelMessageViews' => [
                '_' => 'updateChannelMessageViews',
                'channel_id' => 1737473577,
                'id' => 3541,
                'views' => 4213,
            ],

            // channel_id/date/actor_id/user_id/qts unconditional; prev_participant on flags.0,
            // new_participant on flags.1, invite on flags.2 (all three deliberately unset).
            'updateChannelParticipant' => [
                '_' => 'updateChannelParticipant',
                'flags' => 0,
                'channel_id' => 1737473577,
                'date' => 1724861000,
                'actor_id' => 501558150,
                'user_id' => 501558151,
                'qts' => 4320,
            ],

            // channel_id/messages unconditional; top_msg_id on flags.0, saved_peer_id on
            // flags.1 (both set, both supplied).
            'updateChannelReadMessagesContents' => [
                '_' => 'updateChannelReadMessagesContents',
                // top_msg_id | saved_peer_id
                'flags' => (1 << 0) | (1 << 1),
                'channel_id' => 1737473577,
                'top_msg_id' => 3551,
                'saved_peer_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                'messages' => [3552, 3553],
            ],

            // flags:# channel_id:long pts:flags.0?int — pts left unset, so it is absent.
            'updateChannelTooLong' => [
                '_' => 'updateChannelTooLong',
                'flags' => 0,
                'channel_id' => 1737473577,
            ],

            // channel_id/from_id/action unconditional; top_msg_id on flags.0 (set and supplied).
            'updateChannelUserTyping' => [
                '_' => 'updateChannelUserTyping',
                // top_msg_id
                'flags' => (1 << 0),
                'channel_id' => 1737473577,
                'top_msg_id' => 3561,
                'from_id' => ['_' => 'peerUser', 'user_id' => 501558152],
                'action' => ['_' => 'sendMessageTypingAction'],
            ],

            // channel_id:long enabled:Bool — no flags.
            'updateChannelViewForumAsMessages' => [
                '_' => 'updateChannelViewForumAsMessages',
                'channel_id' => 1737473577,
                'enabled' => true,
            ],

            // channel_id/webpage/pts/pts_count unconditional — webpage is a nested webPagePending.
            'updateChannelWebPage' => [
                '_' => 'updateChannelWebPage',
                'channel_id' => 1737473577,
                'webpage' => [
                    '_' => 'webPagePending',
                    // url
                    'flags' => (1 << 0),
                    'id' => 7770002222222,
                    'url' => 'https://example.invalid/teleframe-fixture-webpage',
                    'date' => 1724861200,
                ],
                'pts' => 3571,
                'pts_count' => 1,
            ],

            // chat_id:long — the single unconditional field.
            'updateChat' => [
                '_' => 'updateChat',
                'chat_id' => -1001234567890,
            ],

            // peer/default_banned_rights/version unconditional — no flags of its own; the
            // nested chatBannedRights carries its own mask.
            'updateChatDefaultBannedRights' => [
                '_' => 'updateChatDefaultBannedRights',
                'peer' => ['_' => 'peerChat', 'chat_id' => -1001234567890],
                'default_banned_rights' => [
                    '_' => 'chatBannedRights',
                    // view_messages | send_messages | send_media | until_date-less set
                    'flags' => (1 << 0) | (1 << 1) | (1 << 2),
                    'view_messages' => true,
                    'send_messages' => true,
                    'send_media' => true,
                    'until_date' => 0,
                ],
                'version' => 7,
            ],

            // chat_id/date/actor_id/user_id/qts unconditional; prev_participant on flags.0,
            // new_participant on flags.1 (both set), invite on flags.2 (left unset).
            'updateChatParticipant' => [
                '_' => 'updateChatParticipant',
                // prev_participant | new_participant
                'flags' => (1 << 0) | (1 << 1),
                'chat_id' => -1001234567890,
                'date' => 1724861300,
                'actor_id' => 501558150,
                'user_id' => 501558153,
                'prev_participant' => [
                    '_' => 'chatParticipant',
                    // rank
                    'flags' => (1 << 0),
                    'user_id' => 501558153,
                    'inviter_id' => 501558149,
                    'date' => 1724859000,
                    'rank' => 'fixture-member',
                ],
                'new_participant' => [
                    '_' => 'chatParticipantAdmin',
                    // rank
                    'flags' => (1 << 0),
                    'user_id' => 501558153,
                    'inviter_id' => 501558149,
                    'date' => 1724861300,
                    'rank' => 'fixture-admin',
                ],
                'qts' => 4321,
            ],

            // chat_id/user_id/inviter_id/date/version — no flags.
            'updateChatParticipantAdd' => [
                '_' => 'updateChatParticipantAdd',
                'chat_id' => -1001234567890,
                'user_id' => 501558154,
                'inviter_id' => 501558149,
                'date' => 1724861400,
                'version' => 8,
            ],

            // chat_id/user_id/is_admin/version — no flags; Bool is a plain decoded bool.
            'updateChatParticipantAdmin' => [
                '_' => 'updateChatParticipantAdmin',
                'chat_id' => -1001234567890,
                'user_id' => 501558155,
                'is_admin' => true,
                'version' => 9,
            ],

            // chat_id/user_id/version — no flags.
            'updateChatParticipantDelete' => [
                '_' => 'updateChatParticipantDelete',
                'chat_id' => -1001234567890,
                'user_id' => 501558154,
                'version' => 10,
            ],

            // chat_id/user_id/rank/version — no flags.
            'updateChatParticipantRank' => [
                '_' => 'updateChatParticipantRank',
                'chat_id' => -1001234567890,
                'user_id' => 501558155,
                'rank' => 'fixture-moderator',
                'version' => 11,
            ],

            // participants:ChatParticipants — a single nested node carrying its own members.
            'updateChatParticipants' => [
                '_' => 'updateChatParticipants',
                'participants' => [
                    '_' => 'chatParticipantsForbidden',
                    // self_participant
                    'flags' => (1 << 0),
                    'chat_id' => -1001234567890,
                    'self_participant' => [
                        '_' => 'chatParticipantCreator',
                        // rank
                        'flags' => (1 << 0),
                        'user_id' => 501558149,
                        'rank' => 'fixture-owner',
                    ],
                ],
            ],

            // chat_id/from_id/action — no flags.
            'updateChatUserTyping' => [
                '_' => 'updateChatUserTyping',
                'chat_id' => -1001234567890,
                'from_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'action' => ['_' => 'sendMessageTypingAction'],
            ],

            // no declared fields at all — the config has been reloaded.
            'updateConfig' => [
                '_' => 'updateConfig',
            ],

            // no declared fields at all — the contacts list was reset.
            'updateContactsReset' => [
                '_' => 'updateContactsReset',
            ],
        ];
    }
}
