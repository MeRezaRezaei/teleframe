<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Realistic decoded payloads for the `E` slice of the update-constructor
 * universe: every `updateXxx` whose first character after the literal string
 * `update` is `E`.
 *
 * MEASURED COUNT: 6.
 *
 * Command that produced the set — parsed constructors, never a grep, because a
 * grep over the `.tl` also counts RPC METHODS (e.g. `messages.setEncryptedTyping`)
 * that are not constructors at all:
 *
 *     cd /home/me/Documents/projects/teleframe && timeout -k 5 180 php -r 'require "vendor/autoload.php";
 *       $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(__DIR__."/schema/sources/TL_telegram_v227.tl");
 *       $n = []; foreach ($s->types() as $t) { foreach (array_keys((array) $t->constructors()) as $k) { $n[(string)$k] = true; } }
 *       $u = array_values(array_filter(array_keys($n), fn($k) => str_starts_with($k, "update")));
 *       sort($u);
 *       foreach ($u as $k) { if (strtoupper(substr($k,6,1)) === "E") echo $k, "\n"; }'
 *
 * Its verbatim output — these six lines and nothing else:
 *
 *     updateEditChannelMessage
 *     updateEditMessage
 *     updateEmojiGameInfo
 *     updateEncryptedChatTyping
 *     updateEncryptedMessagesRead
 *     updateEncryption
 *
 * The count above is derived from that run, not copied from a plan or a table.
 *
 * Declared shapes read out of `schema/sources/TL_telegram_v227.tl` (line numbers
 * at the time of writing) so each payload matches what the decoder will demand:
 *
 *     351: updateEditChannelMessage#1b3f4df7 message:Message pts:int pts_count:int = Update;
 *     353: updateEditMessage#e40370a3        message:Message pts:int pts_count:int = Update;
 *     464: updateEmojiGameInfo#fb9c547a      info:messages.EmojiGameInfo = Update;
 *     324: updateEncryptedChatTyping#1710f156 chat_id:int = Update;
 *     325: updateEncryption#b4a2e88d         chat:EncryptedChat date:int = Update;
 *     326: updateEncryptedMessagesRead#38fe25b7 chat_id:int max_date:int date:int = Update;
 *
 * Accessors used for the derivation: `TlScheme::types()` -> `TlType::constructors()`.
 *
 * SHAPE CONVENTION (identical to tests/Ingest/Concerns/HasNestedUpdateFixtures.php
 * and to the landed sibling groups):
 *  - `_` carries the BARE constructor name on EVERY constructor node, nested ones
 *    included. No `#hex` ids anywhere in a fixture — the hex is the TL declaration,
 *    not the decoded payload.
 *  - set-flag booleans arrive as EXPLICIT keys beside the `flags` bitmask that
 *    turns them on, which is what the wire actually carries.
 *  - vectors are PHP lists of constructor arrays; peers are constructor objects
 *    (`['_','peerUser','user_id' => N]`).
 *  - only fields the constructor DECLARES appear. A `flags`-conditioned field is
 *    present only when its bit is set in the mask provided, so the decoder is never
 *    asked to read a field the wire would not have sent. Nothing the scheme does
 *    not declare is invented — neither `updateEncryptedMessagesRead` nor
 *    `updateEncryptedChatTyping` declares a `pts` at all, so none is present on
 *    them, while both `updateEditMessage` and `updateEditChannelMessage` declare
 *    one and carry it.
 *  - every value is SYNTHETIC: obviously-fake repeated-digit `access_hash`
 *    integers, no session strings, no credentials, `.env` never read.
 *  - ids are distinct per endpoint so nothing collides in the peer-scoped key
 *    (account_id, peer_type, peer_id, id) — see tests/Mirror/MessagePeerScopedPrimaryKeyTest.php.
 *
 * SELF-CHECK: `count(self::payloads()) === 6`, every key has
 * `strtoupper(substr($k, 6, 1)) === 'E'`, and every one of the six derived `E`
 * constructors is present — this group owns the whole letter, and the loader's
 * provided-but-unclassified check has nothing to complain about.
 */
trait GroupE
{
    /**
     * The index-6 letters this group owns.
     *
     * @return list<string>
     */
    public static function letters(): array
    {
        return ['E'];
    }

    /**
     * @return array<string, array<string, mixed>> ctor name => a decoded payload
     */
    public static function payloads(): array
    {
        return [
            // message:Message pts:int pts_count:int — a channel edit carries the peer in
            // message.peer_id, which is where the peer-scoped key gets its peer_type.
            'updateEditChannelMessage' => [
                '_' => 'updateEditChannelMessage',
                'message' => [
                    '_' => 'message',
                    // edit_hide | from_id | media
                    'flags' => (1 << 21) | (1 << 8) | (1 << 9),
                    'flags2' => 0,
                    'id' => 3401,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                    'date' => 1724853200,
                    'edit_date' => 1724853300,
                    'message' => 'Channel fixture message, edited',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'pts' => 1360,
                'pts_count' => 1,
            ],

            // message:Message pts:int pts_count:int — the private-edit twin of the above.
            'updateEditMessage' => [
                '_' => 'updateEditMessage',
                'message' => [
                    '_' => 'message',
                    // out | edit_hide | from_id | media
                    'flags' => (1 << 1) | (1 << 21) | (1 << 8) | (1 << 9),
                    'flags2' => 0,
                    'id' => 3402,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'peer_id' => ['_' => 'peerUser', 'user_id' => 501558150],
                    'date' => 1724853400,
                    'edit_date' => 1724853500,
                    'message' => 'Private fixture message, edited',
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
                'pts' => 1361,
                'pts_count' => 1,
            ],

            // info:messages.EmojiGameInfo — the dice-info ctor with plays_left (flags.0)
            // deliberately left unset, so the decoder is never asked for it.
            'updateEmojiGameInfo' => [
                '_' => 'updateEmojiGameInfo',
                'info' => [
                    '_' => 'messages.emojiGameDiceInfo',
                    'flags' => 0,
                    'game_hash' => 'tf-fixture-game',
                    'prev_stake' => 9000001111111,
                    'current_streak' => 3,
                    'params' => [4, 8, 12],
                ],
            ],

            // chat_id:int — the single unconditional field; an Int-only ctor with no flags.
            'updateEncryptedChatTyping' => [
                '_' => 'updateEncryptedChatTyping',
                'chat_id' => 3403,
            ],

            // chat_id:int max_date:int date:int — all three unconditional, in declared order.
            'updateEncryptedMessagesRead' => [
                '_' => 'updateEncryptedMessagesRead',
                'chat_id' => 3404,
                'max_date' => 1724853600,
                'date' => 1724853700,
            ],

            // chat:EncryptedChat date:int — encryptedChatWaiting is the smallest
            // EncryptedChat with no conditional fields, so nothing is invented.
            'updateEncryption' => [
                '_' => 'updateEncryption',
                'chat' => [
                    '_' => 'encryptedChatWaiting',
                    'id' => 3405,
                    'access_hash' => 7777777777777777777,
                    'date' => 1724853800,
                    'admin_id' => 501558149,
                    'participant_id' => 501558150,
                ],
                'date' => 1724853900,
            ],
        ];
    }
}
