<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Realistic decoded payloads for the 18 layer-227 MTProto endpoints whose
 * constructor name begins `updateM` or `updateN`.
 *
 * Measured count: 18 (M=8, N=10) — matches the expected slice size.
 *
 * Command that produced the set (parsed constructors, NOT a grep — a text count
 * also matches RPC methods; see .brain/decisions/D-016-endpoint-universe-175-and-three-missing-apis.md):
 *
 *     timeout -k 5 180 php -r 'require "vendor/autoload.php";
 *       $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(__DIR__."/schema/sources/TL_telegram_v227.tl");
 *       $n = []; foreach ($s->types() as $t) { foreach (array_keys((array) $t->constructors()) as $k) { $n[(string)$k] = true; } }
 *       $u = array_values(array_filter(array_keys($n), fn($k) => str_starts_with($k, "update")));
 *       sort($u);
 *       $m = array_values(array_filter($u, fn($k) => strtoupper(substr($k,6,1)) === "M"));
 *       $r = array_values(array_filter($u, fn($k) => strtoupper(substr($k,6,1)) === "N"));
 *       echo count($m), " ", count($r), "\n";'   # => 8 10
 *
 * Shape convention — mirrors tests/Ingest/Concerns/HasNestedUpdateFixtures.php:
 *   - `_` is the BARE constructor name on EVERY constructor node. No `#hex` ids anywhere.
 *   - Set-flag booleans arrive as EXPLICIT keys alongside the `flags` mask that turns them on.
 *   - Vectors are PHP lists; peers are objects (`peerUser` / `peerChannel` / `peerChat`).
 *   - Only fields the ctor actually DECLARES appear. A `flags`-conditioned field is
 *     present only when its bit is set in the mask, so the decoder is never asked to
 *     read a field the wire would not have sent.
 *   - Every value is SYNTHETIC. `access_hash` values are obviously-fake repeated-digit
 *     integers. No session strings, no credentials, nothing read from .env.
 *   - Ids are distinct per endpoint so nothing collides in the peer-scoped key
 *     (account_id, peer_type, peer_id, id) — see tests/Mirror/MessagePeerScopedPrimaryKeyTest.php.
 *
 * Self-check: MEASURED 18 endpoints, all with first letter M or N after `update`
 * (updateManagedBot, updateMessageExtendedMedia, updateMessageID, updateMessagePoll,
 * updateMessagePollVote, updateMessageReactions, updateMonoForumNoPaidException,
 * updateMoveStickerSetToTop, updateNewAuthorization, updateNewBotConnection,
 * updateNewChannelMessage, updateNewEncryptedMessage, updateNewMessage,
 * updateNewQuickReply, updateNewScheduledMessage, updateNewStickerSet,
 * updateNewStoryReaction, updateNotifySettings). Zero endpoints outside M/N.
 */
trait GroupMN
{
    /**
     * @return array<string, array<string, mixed>> ctor name => a decoded payload
     */
    public static function payloads(): array
    {
        return [
            // user_id:long bot_id:long qts:int — all three unconditional.
            'updateManagedBot' => [
                '_' => 'updateManagedBot',
                'user_id' => 5550001110001,
                'bot_id' => 5550002220002,
                'qts' => 4410,
            ],

            // peer:Peer msg_id:int extended_media:vector<MessageExtendedMedia> — all unconditional.
            'updateMessageExtendedMedia' => [
                '_' => 'updateMessageExtendedMedia',
                'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                'msg_id' => 2241,
                'extended_media' => [
                    ['_' => 'messageExtendedMedia', 'media' => ['_' => 'messageMediaEmpty']],
                ],
            ],

            // id:int random_id:long — both unconditional.
            'updateMessageID' => [
                '_' => 'updateMessageID',
                'id' => 2251,
                'random_id' => 8880001111111111,
            ],

            // poll_id:long + results:PollResults unconditional; peer/msg_id need flags.1,
            // poll needs flags.0, top_msg_id needs flags.2 (deliberately left unset).
            'updateMessagePoll' => [
                '_' => 'updateMessagePoll',
                'flags' => (1 << 0) | (1 << 1),
                'poll' => [
                    '_' => 'poll',
                    'id' => 7770001111111,
                    'flags' => 0,
                    'question' => ['_' => 'textWithEntities', 'text' => 'Ship the fixtures?', 'entities' => []],
                    'answers' => [
                        [
                            '_' => 'pollAnswer',
                            'flags' => 0,
                            'text' => ['_' => 'textWithEntities', 'text' => 'Yes', 'entities' => []],
                            'option' => 'yes',
                        ],
                        [
                            '_' => 'pollAnswer',
                            'flags' => 0,
                            'text' => ['_' => 'textWithEntities', 'text' => 'No', 'entities' => []],
                            'option' => 'no',
                        ],
                    ],
                    'hash' => 6660001111111,
                ],
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'msg_id' => 2261,
                'poll_id' => 7770001111111,
                'results' => ['_' => 'pollResults', 'flags' => 0],
            ],

            // poll_id:long peer:Peer options:vector<bytes> positions:vector<int> qts:int — no flags.
            'updateMessagePollVote' => [
                '_' => 'updateMessagePollVote',
                'poll_id' => 7770001111111,
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'options' => ['yes', 'no'],
                'positions' => [0, 1],
                'qts' => 4412,
            ],

            // peer:Peer msg_id:int reactions:MessageReactions unconditional; flags.0 => top_msg_id,
            // flags.1 => saved_peer_id (deliberately left unset).
            'updateMessageReactions' => [
                '_' => 'updateMessageReactions',
                'flags' => (1 << 0),
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'msg_id' => 2271,
                'top_msg_id' => 1186,
                'reactions' => [
                    '_' => 'messageReactions',
                    'flags' => 0,
                    'results' => [
                        [
                            '_' => 'reactionCount',
                            'flags' => 0,
                            'reaction' => ['_' => 'reactionEmoji', 'emoticon' => '👍'],
                            'count' => 3,
                        ],
                        [
                            '_' => 'reactionCount',
                            'flags' => 0,
                            'reaction' => ['_' => 'reactionEmpty'],
                            'count' => 0,
                        ],
                    ],
                ],
            ],

            // channel_id:long saved_peer_id:Peer unconditional; flags.0 => exception:true.
            'updateMonoForumNoPaidException' => [
                '_' => 'updateMonoForumNoPaidException',
                'flags' => (1 << 0),
                'exception' => true,
                'channel_id' => 1737473577,
                'saved_peer_id' => ['_' => 'peerUser', 'user_id' => 501558149],
            ],

            // stickerset:long unconditional; masks:true on flags.0, emojis:true on flags.1.
            'updateMoveStickerSetToTop' => [
                '_' => 'updateMoveStickerSetToTop',
                'flags' => (1 << 0) | (1 << 1),
                'masks' => true,
                'emojis' => true,
                'stickerset' => 4440001111111,
            ],

            // hash:long unconditional; unconfirmed/date/device/location all hang off flags.0.
            'updateNewAuthorization' => [
                '_' => 'updateNewAuthorization',
                'flags' => (1 << 0),
                'unconfirmed' => true,
                'hash' => 3330001111111,
                'date' => 1724852500,
                'device' => 'Teleframe Fixture',
                'location' => 'nowhere',
            ],

            // bot_id:long unconditional; confirmed:true on flags.0, date/device/location on flags.1.
            'updateNewBotConnection' => [
                '_' => 'updateNewBotConnection',
                'flags' => (1 << 0) | (1 << 1),
                'confirmed' => true,
                'bot_id' => 5550002220002,
                'date' => 1724852600,
                'device' => 'Teleframe Fixture',
                'location' => 'nowhere',
            ],

            // message:Message pts:int pts_count:int — message needs peerChannel.
            'updateNewChannelMessage' => [
                '_' => 'updateNewChannelMessage',
                'message' => [
                    '_' => 'message',
                    'flags' => (1 << 8),
                    'flags2' => 0,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'id' => 2281,
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                    'date' => 1724852700,
                    'message' => 'Channel fixture message',
                ],
                'pts' => 1350,
                'pts_count' => 1,
            ],

            // message:EncryptedMessage qts:int — encryptedFileEmpty is paramless.
            'updateNewEncryptedMessage' => [
                '_' => 'updateNewEncryptedMessage',
                'message' => [
                    '_' => 'encryptedMessage',
                    'random_id' => 8880002222222222,
                    'chat_id' => 501558149,
                    'date' => 1724852800,
                    'bytes' => 'synthetic-cipher-bytes',
                    'file' => ['_' => 'encryptedFileEmpty'],
                ],
                'qts' => 4414,
            ],

            // message:Message pts:int pts_count:int — the canonical HasNestedUpdateFixtures shape.
            'updateNewMessage' => [
                '_' => 'updateNewMessage',
                'message' => [
                    '_' => 'message',
                    // out | media
                    'flags' => (1 << 1) | (1 << 9),
                    'out' => true,
                    'flags2' => 0,
                    'id' => 2291,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                    'date' => 1724852900,
                    'message' => 'Private fixture message',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'pts' => 1351,
                'pts_count' => 1,
            ],

            // quick_reply:QuickReply — the single unconditional field.
            'updateNewQuickReply' => [
                '_' => 'updateNewQuickReply',
                'quick_reply' => [
                    '_' => 'quickReply',
                    'shortcut_id' => 900,
                    'shortcut' => 'fixture',
                    'top_message' => 2291,
                    'count' => 0,
                ],
            ],

            // message:Message — no pts pair on this ctor, so none is invented.
            'updateNewScheduledMessage' => [
                '_' => 'updateNewScheduledMessage',
                'message' => [
                    '_' => 'message',
                    'flags' => (1 << 9),
                    'flags2' => 0,
                    'id' => 2301,
                    'peer_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'date' => 1724853000,
                    'message' => 'Scheduled fixture message',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
            ],

            // stickerset:messages.StickerSet — full set ctor, empty packs/keywords/documents.
            'updateNewStickerSet' => [
                '_' => 'updateNewStickerSet',
                'stickerset' => [
                    '_' => 'messages.stickerSet',
                    'set' => [
                        '_' => 'stickerSet',
                        // archived
                        'flags' => (1 << 1),
                        'id' => 4440001111111,
                        'access_hash' => 1111111111111111111,
                        'title' => 'Teleframe Fixture Pack',
                        'short_name' => 'tf_fixture',
                        'count' => 2,
                        'hash' => 222222222,
                    ],
                    'packs' => [],
                    'keywords' => [],
                    'documents' => [],
                ],
            ],

            // story_id:int peer:Peer reaction:Reaction — all three unconditional.
            'updateNewStoryReaction' => [
                '_' => 'updateNewStoryReaction',
                'story_id' => 41,
                'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                'reaction' => ['_' => 'reactionEmoji', 'emoticon' => '🎉'],
            ],

            // peer:NotifyPeer notify_settings:PeerNotifySettings — both unconditional.
            'updateNotifySettings' => [
                '_' => 'updateNotifySettings',
                'peer' => [
                    '_' => 'notifyPeer',
                    'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                ],
                'notify_settings' => ['_' => 'peerNotifySettings', 'flags' => 0],
            ],
        ];
    }
}
