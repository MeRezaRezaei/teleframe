<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Realistic decoded payloads for the P/R/Q slice of the update-constructor
 * universe: every `updateXxx` whose first letter after `update` is P, R or Q.
 *
 * MEASURED COUNT: 34 (P = 17, R = 15, Q = 2).
 *
 * Command that produced the set (constructor map only — never a grep, because
 * a grep also counts METHODS such as `updates.getDifference`):
 *
 *   php -r 'require "vendor/autoload.php";
 *   $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(
 *       __DIR__."/schema/sources/TL_telegram_v227.tl");
 *   foreach ($s->types() as $t) {
 *       foreach ($t->constructors() as $k => $c) {
 *           $k = (string) $k;
 *           if (str_starts_with($k, "update")) { $n[$k] = true; }
 *       }
 *   }
 *   ksort($n);
 *   foreach (array_keys($n) as $k) {
 *       if (in_array(strtoupper(substr($k, 6, 1)), ["P","R","Q"], true)) { echo $k, "\n"; }
 *   }'
 *
 * Accessors actually used: `TlScheme::types()` -> `TlType::constructors()` ->
 * `TlConstructor::allParams()` -> `TlParam::$token` (the raw declared type).
 *
 * SHAPE CONVENTION (identical to tests/Ingest/Concerns/HasNestedUpdateFixtures.php):
 *  - `_` carries the BARE constructor name on EVERY constructor node, nested
 *    ones included; no `#hex` ids anywhere in a fixture.
 *  - set-flag booleans arrive as EXPLICIT keys beside the `flags` bitmask
 *    (`'flags' => (1 << 0), 'blocked' => true`) — this is what the decoder
 *    actually sees on the wire.
 *  - vectors are PHP lists of constructor arrays; peers are constructor
 *    objects (`['_','peerUser','user_id' => N]`).
 *  - every field a constructor declares (except `flags` bitmask bookkeeping)
 *    is present; nothing the scheme does not declare is invented.
 *  - every value is SYNTHETIC: obviously-fake access hashes, no credentials,
 *    no session strings, `.env` never read.
 *
 * SELF-CHECK: `count(self::payloads()) === 34` measured over the constructor
 * map (see the command above); P=17, R=15, Q=2. Note the R slice is 15
 * constructors, not 14: the method `updates.getDifference` is an RPC, not a
 * constructor, so its `updates.difference` result is out of scope here — the
 * 15 constructors listed here are the whole R letter.
 */
trait GroupPRQ
{
    /**
     * @return array<string, array<string, mixed>> ctor name => a decoded payload
     */
    public static function payloads(): array
    {
        return [
            // ---------------------------------------------------------------
            // P — 17 constructors
            // ---------------------------------------------------------------
            'updatePaidReactionPrivacy' => [
                '_' => 'updatePaidReactionPrivacy',
                'private' => ['_' => 'paidReactionPrivacyDefault'],
            ],
            'updatePeerBlocked' => [
                '_' => 'updatePeerBlocked',
                // blocked | blocked_my_stories_from
                'flags' => (1 << 0) | (1 << 1),
                'blocked' => true,
                'blocked_my_stories_from' => true,
                'peer_id' => ['_' => 'peerUser', 'user_id' => 501558149],
            ],
            'updatePeerHistoryTTL' => [
                '_' => 'updatePeerHistoryTTL',
                'flags' => (1 << 0),
                'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                'ttl_period' => 86400,
            ],
            'updatePeerLocated' => [
                '_' => 'updatePeerLocated',
                'peers' => [
                    [
                        '_' => 'peerLocated',
                        'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                        'expires' => 1724852500,
                        'distance' => 120,
                    ],
                ],
            ],
            'updatePeerSettings' => [
                '_' => 'updatePeerSettings',
                'peer' => ['_' => 'peerChat', 'chat_id' => -1001234567890],
                'settings' => [
                    '_' => 'peerSettings',
                    // report_spam | add_contact | share_contact | geo_distance
                    'flags' => (1 << 0) | (1 << 1) | (1 << 3) | (1 << 6),
                    'report_spam' => true,
                    'add_contact' => true,
                    'share_contact' => true,
                    'geo_distance' => 340,
                ],
            ],
            'updatePeerWallpaper' => [
                '_' => 'updatePeerWallpaper',
                // wallpaper | wallpaper_overridden
                'flags' => (1 << 0) | (1 << 1),
                'wallpaper_overridden' => true,
                'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                'wallpaper' => [
                    '_' => 'wallPaperNoFile',
                    'id' => -1009876543210,
                    // default | dark
                    'flags' => (1 << 1) | (1 << 4),
                    'default' => true,
                    'dark' => true,
                ],
            ],
            'updatePendingJoinRequests' => [
                '_' => 'updatePendingJoinRequests',
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'requests_pending' => 4,
                'recent_requesters' => [501558149, 501558150],
            ],
            'updatePhoneCall' => [
                '_' => 'updatePhoneCall',
                'phone_call' => [
                    '_' => 'phoneCallWaiting',
                    // video
                    'flags' => (1 << 6),
                    'video' => true,
                    'id' => 7700112233445566,
                    'access_hash' => -4001223344556677888,
                    'date' => 1724852600,
                    'admin_id' => 501558149,
                    'participant_id' => 501558150,
                    'protocol' => [
                        '_' => 'phoneCallProtocol',
                        // udp_p2p | udp_reflector
                        'flags' => (1 << 0) | (1 << 1),
                        'udp_p2p' => true,
                        'udp_reflector' => true,
                        'min_layer' => 92,
                        'max_layer' => 140,
                        'library_versions' => ['2.0.0'],
                    ],
                ],
            ],
            'updatePhoneCallSignalingData' => [
                '_' => 'updatePhoneCallSignalingData',
                'phone_call_id' => 7700112233445566,
                'data' => base64_encode('tg-signal-fake-payload-01'),
            ],
            'updatePinnedChannelMessages' => [
                '_' => 'updatePinnedChannelMessages',
                // pinned
                'flags' => (1 << 0),
                'pinned' => true,
                'channel_id' => 1737473577,
                'messages' => [1186, 1187],
                'pts' => 2101,
                'pts_count' => 2,
            ],
            'updatePinnedDialogs' => [
                '_' => 'updatePinnedDialogs',
                // order | folder_id
                'flags' => (1 << 0) | (1 << 1),
                'folder_id' => 3,
                'order' => [
                    ['_' => 'dialogPeer', 'peer' => ['_' => 'peerUser', 'user_id' => 501558149]],
                    ['_' => 'dialogPeerFolder', 'folder_id' => 3],
                ],
            ],
            'updatePinnedForumTopic' => [
                '_' => 'updatePinnedForumTopic',
                // pinned
                'flags' => (1 << 0),
                'pinned' => true,
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'topic_id' => 42,
            ],
            'updatePinnedForumTopics' => [
                '_' => 'updatePinnedForumTopics',
                // order
                'flags' => (1 << 0),
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'order' => [42, 43],
            ],
            'updatePinnedMessages' => [
                '_' => 'updatePinnedMessages',
                // pinned
                'flags' => (1 << 0),
                'pinned' => true,
                'peer' => ['_' => 'peerChat', 'chat_id' => -1001234567890],
                'messages' => [2201, 2202],
                'pts' => 2102,
                'pts_count' => 2,
            ],
            'updatePinnedSavedDialogs' => [
                '_' => 'updatePinnedSavedDialogs',
                // order
                'flags' => (1 << 0),
                'order' => [
                    ['_' => 'dialogPeer', 'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577]],
                    ['_' => 'dialogPeerFolder', 'folder_id' => 1],
                ],
            ],
            'updatePrivacy' => [
                '_' => 'updatePrivacy',
                'key' => ['_' => 'privacyKeyStatusTimestamp'],
                'rules' => [
                    ['_' => 'privacyValueAllowUsers', 'users' => [501558149, 501558150]],
                    ['_' => 'privacyValueDisallowContacts'],
                ],
            ],
            'updatePtsChanged' => [
                '_' => 'updatePtsChanged',
            ],

            // ---------------------------------------------------------------
            // Q — 2 constructors
            // ---------------------------------------------------------------
            'updateQuickReplies' => [
                '_' => 'updateQuickReplies',
                'quick_replies' => [
                    ['_' => 'quickReply', 'shortcut_id' => 1, 'shortcut' => 'tg', 'top_message' => 1186, 'count' => 12],
                    ['_' => 'quickReply', 'shortcut_id' => 2, 'shortcut' => 'ok', 'top_message' => 1187, 'count' => 3],
                ],
            ],
            'updateQuickReplyMessage' => [
                '_' => 'updateQuickReplyMessage',
                'message' => [
                    '_' => 'message',
                    // from_id | media | quick_reply_shortcut_id
                    'flags' => (1 << 8) | (1 << 9) | (1 << 30),
                    'flags2' => 0,
                    'id' => 3301,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChat', 'chat_id' => -1001234567890],
                    'date' => 1724852700,
                    'message' => 'Yep, replying from a quick reply.',
                    'media' => ['_' => 'messageMediaEmpty'],
                    'quick_reply_shortcut_id' => 1,
                ],
            ],

            // ---------------------------------------------------------------
            // R — 15 constructors
            // ---------------------------------------------------------------
            'updateReadChannelDiscussionInbox' => [
                '_' => 'updateReadChannelDiscussionInbox',
                // broadcast_id | broadcast_post
                'flags' => (1 << 0),
                'channel_id' => 1737473577,
                'top_msg_id' => 1200,
                'read_max_id' => 1186,
                'broadcast_id' => 8800112233445566,
                'broadcast_post' => 55,
            ],
            'updateReadChannelDiscussionOutbox' => [
                '_' => 'updateReadChannelDiscussionOutbox',
                'channel_id' => 1737473577,
                'top_msg_id' => 1201,
                'read_max_id' => 1187,
            ],
            'updateReadChannelInbox' => [
                '_' => 'updateReadChannelInbox',
                // folder_id
                'flags' => (1 << 0),
                'folder_id' => 2,
                'channel_id' => 1737473577,
                'max_id' => 1188,
                'still_unread_count' => 5,
                'pts' => 2103,
            ],
            'updateReadChannelOutbox' => [
                '_' => 'updateReadChannelOutbox',
                'channel_id' => 1737473577,
                'max_id' => 1189,
            ],
            'updateReadFeaturedEmojiStickers' => [
                '_' => 'updateReadFeaturedEmojiStickers',
            ],
            'updateReadFeaturedStickers' => [
                '_' => 'updateReadFeaturedStickers',
            ],
            'updateReadHistoryInbox' => [
                '_' => 'updateReadHistoryInbox',
                // folder_id | top_msg_id
                'flags' => (1 << 0) | (1 << 1),
                'folder_id' => 1,
                'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                'top_msg_id' => 1195,
                'max_id' => 1190,
                'still_unread_count' => 7,
                'pts' => 2104,
                'pts_count' => 1,
            ],
            'updateReadHistoryOutbox' => [
                '_' => 'updateReadHistoryOutbox',
                'peer' => ['_' => 'peerChat', 'chat_id' => -1001234567890],
                'max_id' => 2203,
                'pts' => 2105,
                'pts_count' => 1,
            ],
            'updateReadMessagesContents' => [
                '_' => 'updateReadMessagesContents',
                // date
                'flags' => (1 << 0),
                'messages' => [3301, 3302],
                'pts' => 2106,
                'pts_count' => 2,
                'date' => 1724852800,
            ],
            'updateReadMonoForumInbox' => [
                '_' => 'updateReadMonoForumInbox',
                'channel_id' => 1737473577,
                'saved_peer_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                'read_max_id' => 4401,
            ],
            'updateReadMonoForumOutbox' => [
                '_' => 'updateReadMonoForumOutbox',
                'channel_id' => 1737473577,
                'saved_peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473578],
                'read_max_id' => 4402,
            ],
            'updateReadStories' => [
                '_' => 'updateReadStories',
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'max_id' => 5501,
            ],
            'updateRecentEmojiStatuses' => [
                '_' => 'updateRecentEmojiStatuses',
            ],
            'updateRecentReactions' => [
                '_' => 'updateRecentReactions',
            ],
            'updateRecentStickers' => [
                '_' => 'updateRecentStickers',
            ],
        ];
    }
}
