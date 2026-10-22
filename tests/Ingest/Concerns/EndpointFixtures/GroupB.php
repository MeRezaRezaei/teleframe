<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Realistic decoded payloads for the `B` slice of the layer-227 update-constructor
 * universe: every `updateXxx` whose first character after the literal string
 * `update` is `B`.
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
 *       foreach ($u as $k) { if (strtoupper(substr($k,6,1)) === "B") echo $k, "\n"; }'
 *
 * Verbatim output of that command (21 lines, exactly the keys of payloads() below):
 *
 *     updateBotBusinessConnect
 *     updateBotCallbackQuery
 *     updateBotChatBoost
 *     updateBotChatInviteRequester
 *     updateBotCommands
 *     updateBotDeleteBusinessMessage
 *     updateBotEditBusinessMessage
 *     updateBotGuestChatQuery
 *     updateBotInlineQuery
 *     updateBotInlineSend
 *     updateBotMenuButton
 *     updateBotMessageReaction
 *     updateBotMessageReactions
 *     updateBotNewBusinessMessage
 *     updateBotPrecheckoutQuery
 *     updateBotPurchasedPaidMedia
 *     updateBotShippingQuery
 *     updateBotStopped
 *     updateBotWebhookJSON
 *     updateBotWebhookJSONQuery
 *     updateBusinessBotCallbackQuery
 *
 * Field lists were read out of the constructor blocks in
 * `schema/sources/TL_telegram_v227.tl` (lines 349-352, 364-368, 404-412, 428-432,
 * 442-450, 468), and nested types out of `botBusinessConnection` (1870),
 * `boost` (1767), `chatInviteExported` (670), `botCommand` (695), `botMenuButton`
 * (1550), `paymentRequestedInfo` (992), `postAddress` (990), `dataJSON` (982),
 * `geoPoint` (226), `inputBotInlineMessageID` (847), `reaction*` (1610-1612),
 * `reactionCount` (1514) and `message` (121).
 *
 * SHAPE CONVENTION — identical to tests/Ingest/Concerns/HasNestedUpdateFixtures.php
 * and to the landed siblings in this directory:
 *  - `_` carries the BARE constructor name on EVERY constructor node, nested ones
 *    included; no `#hex` ids anywhere in a fixture.
 *  - set-flag booleans arrive as EXPLICIT keys beside the `flags` bitmask that
 *    turns them on (`'flags' => (1 << 0), 'geo' => [...]`) — that is what the
 *    decoder actually sees on the wire.
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
 * constructor map; every key has `strtoupper(substr($k, 6, 1)) === 'B'` and every
 * derived `B` constructor is present, so this group owns the whole letter.
 */
trait GroupB
{
    /**
     * The index-6 letters this group owns.
     *
     * @return list<string>
     */
    public static function letters(): array
    {
        return ['B'];
    }

    /**
     * @return array<string, array<string, mixed>> ctor name => a decoded payload
     */
    public static function payloads(): array
    {
        return [
            // connection:BotBusinessConnection qts:int — no flags.
            'updateBotBusinessConnect' => [
                '_' => 'updateBotBusinessConnect',
                'connection' => [
                    '_' => 'botBusinessConnection',
                    // can_reply | disabled | rights
                    'flags' => (1 << 0) | (1 << 1) | (1 << 2),
                    'disabled' => true,
                    'connection_id' => 'bcn_fixture_0001',
                    'user_id' => 501558149,
                    'dc_id' => 2,
                    'date' => 1724851000,
                    'rights' => [
                        '_' => 'businessBotRights',
                        // reply | read_messages | delete_sent_messages
                        'flags' => (1 << 0) | (1 << 1) | (1 << 2),
                        'reply' => true,
                        'read_messages' => true,
                        'delete_sent_messages' => true,
                    ],
                ],
                'qts' => 4300,
            ],

            // query_id/user_id/peer/msg_id/chat_instance unconditional; data on flags.0,
            // game_short_name on flags.1 (both set).
            'updateBotCallbackQuery' => [
                '_' => 'updateBotCallbackQuery',
                // data | game_short_name
                'flags' => (1 << 0) | (1 << 1),
                'query_id' => 6600001111111111,
                'user_id' => 501558149,
                'peer' => ['_' => 'peerUser', 'user_id' => 501558150],
                'msg_id' => 3401,
                'chat_instance' => 6600002222222222,
                'data' => 'fixture-callback-data',
                'game_short_name' => 'tf_fixture_game',
            ],

            // peer:Peer boost:Boost qts:int — no flags; boost is a nested boost#.
            'updateBotChatBoost' => [
                '_' => 'updateBotChatBoost',
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'boost' => [
                    '_' => 'boost',
                    // user | giveaway | stars
                    'flags' => (1 << 0) | (1 << 2) | (1 << 6),
                    'id' => 'boost_fixture_0001',
                    'user_id' => 501558149,
                    'giveaway' => true,
                    'giveaway_msg_id' => 3402,
                    'date' => 1724851100,
                    'expires' => 1737451100,
                    'stars' => 4440001111111,
                ],
                'qts' => 4301,
            ],

            // peer/date/user_id/about/invite/qts unconditional; query_id on flags.0.
            'updateBotChatInviteRequester' => [
                '_' => 'updateBotChatInviteRequester',
                // query_id
                'flags' => (1 << 0),
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'date' => 1724851200,
                'user_id' => 501558149,
                'about' => 'May I join the fixture channel?',
                'invite' => [
                    '_' => 'chatInviteExported',
                    // permanent
                    'flags' => (1 << 5),
                    'permanent' => true,
                    'link' => 'https://t.me/+fixture_invite_link',
                    'admin_id' => 501558149,
                    'date' => 1724850000,
                ],
                'qts' => 4302,
                'query_id' => 6600003333333333,
            ],

            // peer:Peer bot_id:long commands:Vector<BotCommand> — no flags.
            'updateBotCommands' => [
                '_' => 'updateBotCommands',
                'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                'bot_id' => 5550002220002,
                'commands' => [
                    ['_' => 'botCommand', 'command' => 'start', 'description' => 'Start the fixture'],
                    ['_' => 'botCommand', 'command' => 'stop', 'description' => 'Stop the fixture'],
                ],
            ],

            // connection_id:string peer:Peer messages:Vector<int> qts:int — no flags.
            'updateBotDeleteBusinessMessage' => [
                '_' => 'updateBotDeleteBusinessMessage',
                'connection_id' => 'bcn_fixture_0001',
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'messages' => [3411, 3412],
                'qts' => 4303,
            ],

            // connection_id/message/qts unconditional; reply_to_message on flags.0.
            'updateBotEditBusinessMessage' => [
                '_' => 'updateBotEditBusinessMessage',
                // reply_to_message
                'flags' => (1 << 0),
                'connection_id' => 'bcn_fixture_0001',
                'message' => [
                    '_' => 'message',
                    // media
                    'flags' => (1 << 9),
                    'flags2' => 0,
                    'id' => 3421,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                    'date' => 1724851300,
                    'message' => 'Edited business fixture message',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'reply_to_message' => [
                    '_' => 'message',
                    // media
                    'flags' => (1 << 9),
                    'flags2' => 0,
                    'id' => 3420,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                    'date' => 1724851250,
                    'message' => 'Original business fixture message',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'qts' => 4304,
            ],

            // query_id/message/qts unconditional; reference_messages on flags.0.
            'updateBotGuestChatQuery' => [
                '_' => 'updateBotGuestChatQuery',
                // reference_messages
                'flags' => (1 << 0),
                'query_id' => 6600004444444444,
                'message' => [
                    '_' => 'message',
                    // media
                    'flags' => (1 << 9),
                    'flags2' => 0,
                    'id' => 3431,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChat', 'chat_id' => -1001234567890],
                    'date' => 1724851400,
                    'message' => 'Guest chat fixture message',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'reference_messages' => [
                    [
                        '_' => 'message',
                        // media
                        'flags' => (1 << 9),
                        'flags2' => 0,
                        'id' => 3430,
                        'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                        'peer_id' => ['_' => 'peerChat', 'chat_id' => -1001234567890],
                        'date' => 1724851350,
                        'message' => 'Referenced guest chat fixture message',
                        'media' => ['_' => 'messageMediaEmpty'],
                    ],
                ],
                'qts' => 4305,
            ],

            // query_id/user_id/query/offset unconditional; geo on flags.0, peer_type on flags.1.
            'updateBotInlineQuery' => [
                '_' => 'updateBotInlineQuery',
                // geo | peer_type
                'flags' => (1 << 0) | (1 << 1),
                'query_id' => 6600005555555555,
                'user_id' => 501558149,
                'query' => 'teleframe fixture inline query',
                'geo' => [
                    '_' => 'geoPoint',
                    // access_hash
                    'flags' => (1 << 0),
                    'long' => 51.507351,
                    'lat' => 51.507351,
                    'access_hash' => 1111111111111111111,
                    'accuracy_radius' => 42,
                ],
                'peer_type' => ['_' => 'inlineQueryPeerTypeSameBotPM'],
                'offset' => '',
            ],

            // user_id/query/id/offset unconditional; geo on flags.0, msg_id on flags.1.
            'updateBotInlineSend' => [
                '_' => 'updateBotInlineSend',
                // geo | msg_id
                'flags' => (1 << 0) | (1 << 1),
                'user_id' => 501558149,
                'query' => 'teleframe fixture inline send',
                'geo' => [
                    '_' => 'geoPoint',
                    // access_hash
                    'flags' => (1 << 0),
                    'long' => 35.689247,
                    'lat' => 35.689247,
                    'access_hash' => 2222222222222222222,
                    'accuracy_radius' => 17,
                ],
                'id' => 'inline-send-fixture-id-0001',
                'msg_id' => [
                    '_' => 'inputBotInlineMessageID',
                    'dc_id' => 2,
                    'id' => 3441,
                    'access_hash' => 3333333333333333333,
                ],
            ],

            // bot_id:long button:BotMenuButton — no flags.
            'updateBotMenuButton' => [
                '_' => 'updateBotMenuButton',
                'bot_id' => 5550002220002,
                'button' => [
                    '_' => 'botMenuButton',
                    'text' => 'Open the fixture dashboard',
                    'url' => 'https://example.invalid/teleframe-fixture',
                ],
            ],

            // peer/msg_id/date/actor/old_reactions/new_reactions/qts — no flags.
            'updateBotMessageReaction' => [
                '_' => 'updateBotMessageReaction',
                'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                'msg_id' => 3451,
                'date' => 1724851500,
                'actor' => ['_' => 'peerUser', 'user_id' => 501558150],
                'old_reactions' => [
                    ['_' => 'reactionEmoji', 'emoticon' => '👍'],
                ],
                'new_reactions' => [
                    ['_' => 'reactionEmoji', 'emoticon' => '🔥'],
                ],
                'qts' => 4306,
            ],

            // peer/msg_id/date/reactions/qts — no flags.
            'updateBotMessageReactions' => [
                '_' => 'updateBotMessageReactions',
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'msg_id' => 3461,
                'date' => 1724851600,
                'reactions' => [
                    [
                        '_' => 'reactionCount',
                        // chosen_order
                        'flags' => (1 << 0),
                        'chosen_order' => 0,
                        'reaction' => ['_' => 'reactionEmoji', 'emoticon' => '🎉'],
                        'count' => 7,
                    ],
                    [
                        '_' => 'reactionCount',
                        'flags' => 0,
                        'reaction' => ['_' => 'reactionCustomEmoji', 'document_id' => 5550003330003],
                        'count' => 1,
                    ],
                ],
                'qts' => 4307,
            ],

            // connection_id/message/qts unconditional; reply_to_message on flags.0.
            'updateBotNewBusinessMessage' => [
                '_' => 'updateBotNewBusinessMessage',
                // reply_to_message
                'flags' => (1 << 0),
                'connection_id' => 'bcn_fixture_0001',
                'message' => [
                    '_' => 'message',
                    // media
                    'flags' => (1 << 9),
                    'flags2' => 0,
                    'id' => 3471,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                    'date' => 1724851700,
                    'message' => 'New business fixture message',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'reply_to_message' => [
                    '_' => 'message',
                    // media
                    'flags' => (1 << 9),
                    'flags2' => 0,
                    'id' => 3470,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                    'date' => 1724851650,
                    'message' => 'Quoted business fixture message',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'qts' => 4308,
            ],

            // query_id/user_id/payload/currency/total_amount unconditional; info on flags.0,
            // shipping_option_id on flags.1.
            'updateBotPrecheckoutQuery' => [
                '_' => 'updateBotPrecheckoutQuery',
                // info | shipping_option_id
                'flags' => (1 << 0) | (1 << 1),
                'query_id' => 6600006666666666,
                'user_id' => 501558149,
                'payload' => 'fixture-precheckout-payload',
                'info' => [
                    '_' => 'paymentRequestedInfo',
                    // name | phone | email
                    'flags' => (1 << 0) | (1 << 1) | (1 << 2),
                    'name' => 'Fixture Buyer',
                    'phone' => '+10000000000',
                    'email' => 'fixture@example.invalid',
                ],
                'shipping_option_id' => 'ship_fixture_01',
                'currency' => 'USD',
                'total_amount' => 1999,
            ],

            // user_id:long payload:string qts:int — no flags.
            'updateBotPurchasedPaidMedia' => [
                '_' => 'updateBotPurchasedPaidMedia',
                'user_id' => 501558149,
                'payload' => 'fixture-paid-media-payload',
                'qts' => 4309,
            ],

            // query_id/user_id/payload/shipping_address — no flags.
            'updateBotShippingQuery' => [
                '_' => 'updateBotShippingQuery',
                'query_id' => 6600007777777777,
                'user_id' => 501558149,
                'payload' => 'fixture-shipping-payload',
                'shipping_address' => [
                    '_' => 'postAddress',
                    'street_line1' => '1 Fixture Street',
                    'street_line2' => 'Apt 2',
                    'city' => 'Fixtureville',
                    'state' => 'FixtureState',
                    'country_iso2' => 'US',
                    'post_code' => '00000',
                ],
            ],

            // user_id:long date:int stopped:Bool qts:int — no flags.
            'updateBotStopped' => [
                '_' => 'updateBotStopped',
                'user_id' => 501558149,
                'date' => 1724851800,
                'stopped' => true,
                'qts' => 4310,
            ],

            // data:DataJSON — the single unconditional field.
            'updateBotWebhookJSON' => [
                '_' => 'updateBotWebhookJSON',
                'data' => [
                    '_' => 'dataJSON',
                    'data' => '{"fixture":"teleframe","event":"webhook-json"}',
                ],
            ],

            // query_id:long data:DataJSON timeout:int — no flags.
            'updateBotWebhookJSONQuery' => [
                '_' => 'updateBotWebhookJSONQuery',
                'query_id' => 6600008888888888,
                'data' => [
                    '_' => 'dataJSON',
                    'data' => '{"fixture":"teleframe","event":"webhook-json-query"}',
                ],
                'timeout' => 15,
            ],

            // query_id/user_id/connection_id/message/chat_instance unconditional;
            // data on flags.0, reply_to_message on flags.2 (both set).
            'updateBusinessBotCallbackQuery' => [
                '_' => 'updateBusinessBotCallbackQuery',
                // data | reply_to_message
                'flags' => (1 << 0) | (1 << 2),
                'query_id' => 6600009999999999,
                'user_id' => 501558149,
                'connection_id' => 'bcn_fixture_0001',
                'message' => [
                    '_' => 'message',
                    // media
                    'flags' => (1 << 9),
                    'flags2' => 0,
                    'id' => 3481,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                    'date' => 1724851900,
                    'message' => 'Business callback fixture message',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'reply_to_message' => [
                    '_' => 'message',
                    // media
                    'flags' => (1 << 9),
                    'flags2' => 0,
                    'id' => 3480,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                    'date' => 1724851850,
                    'message' => 'Replied business fixture message',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'chat_instance' => 6600011111111111,
                'data' => 'fixture-business-callback',
            ],
        ];
    }
}
