<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Realistic decoded payloads for the layer-227 MTProto endpoints whose
 * constructor name has `D` at index 6 — the first character after the literal
 * string `update`, uppercased. Ownership is that character, so nothing outside
 * `D` may appear here: `updateDeleteMessages` -> `D`, `updateDraftMessage` ->
 * `D`, and `updateDialogFilterOrder` -> `D` all belong to this group.
 *
 * MEASURED COUNT: 13 endpoints.
 *
 * Command that produced the set (parsed constructors, NOT a grep — a text count
 * also matches RPC METHODS such as `updates.deleteHistory`; see
 * .brain/decisions/D-016-endpoint-universe-175-and-three-missing-apis.md):
 *
 *     cd /home/me/Documents/projects/teleframe && timeout -k 5 180 php -r 'require "vendor/autoload.php";
 *       $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(__DIR__."/schema/sources/TL_telegram_v227.tl");
 *       $n = []; foreach ($s->types() as $t) { foreach (array_keys((array) $t->constructors()) as $k) { $n[(string)$k] = true; } }
 *       $u = array_values(array_filter(array_keys($n), fn($k) => str_starts_with($k, "update")));
 *       sort($u);
 *       foreach ($u as $k) { if (strtoupper(substr($k,6,1)) === "D") echo $k, "\n"; }'
 *
 * Its verbatim output (13 lines, and this is the whole D letter):
 *
 *     updateDcOptions
 *     updateDeleteChannelMessages
 *     updateDeleteGroupCallMessages
 *     updateDeleteMessages
 *     updateDeleteQuickReply
 *     updateDeleteQuickReplyMessages
 *     updateDeleteScheduledMessages
 *     updateDialogFilter
 *     updateDialogFilterOrder
 *     updateDialogFilters
 *     updateDialogPinned
 *     updateDialogUnreadMark
 *     updateDraftMessage
 *
 * Declared fields were read out of `schema/sources/TL_telegram_v227.tl`
 * (`grep -n "^updateD" …`, lines 316-461) plus the nested constructors this
 * group touches (`dcOption#18b7a10d`, `draftMessage#60fe3294`, `dialogPeer#e56dbf05`,
 * `dialogFilter#aa472651`, `inputGroupCall#d8aa840f`, `inputPeerUser#dde8a54c`,
 * `inputPeerChat#35a95cb9`) so each payload matches what the decoder demands.
 *
 * SHAPE CONVENTION (mirrors tests/Ingest/Concerns/HasNestedUpdateFixtures.php,
 * and is what Loader::assertWireShaped enforces):
 *   - `_` carries the BARE constructor name on EVERY constructor node, root and
 *     nested alike. No `#hex` ids anywhere — that is the TL DECLARATION, not the
 *     decoded payload.
 *   - set-flag booleans arrive as EXPLICIT keys beside the `flags` bitmask that
 *     turns them on (`'flags' => (1 << 0), 'pinned' => true`).
 *   - vectors are PHP lists; peers are objects (`peerUser#user_id`,
 *     `peerChannel#channel_id`, `peerChat#chat_id`) and DialogPeers are objects
 *     too (`dialogPeer` wraps a `Peer`).
 *   - Only fields the constructor DECLARES appear. A `flags`-conditioned field is
 *     present only when its bit IS set in the mask supplied here, so the decoder
 *     is never asked to read a field the wire would not have sent. Nothing is
 *     invented to fill a shape.
 *   - Every value is SYNTHETIC. `access_hash` values are obviously-fake integers,
 *     no session string, no credential, nothing read from `.env`.
 *   - Ids are distinct per endpoint so nothing collides in the peer-scoped key
 *     (account_id, peer_type, peer_id, id) — see
 *     tests/Mirror/MessagePeerScopedPrimaryKeyTest.php.
 *
 * SELF-CHECK: `count(self::payloads()) === 13`, and every key satisfies
 * `strtoupper(substr($key, 6, 1)) === 'D'` — zero endpoints outside the letter.
 * `updateDeleteMessages` also appears in `HasEndpointFixtures::baseEndpointFixtures()`
 *; that is deliberate and not a collision: the merge there is BASE-WINS, so the
 * curated worked example stays authoritative and this entry is simply superseded.
 */
trait GroupD
{
    /**
     * The letters this group owns, UPPERCASE — the character at index 6 of each
     * constructor name, after the `update` prefix.
     *
     * @return list<string>
     */
    public static function letters(): array
    {
        return ['D'];
    }

    /**
     * @return array<string, array<string, mixed>> ctor name => a decoded payload
     */
    public static function payloads(): array
    {
        return [
            // dc_options:Vector<DcOption> — the single unconditional field.
            // dcOption: id, ip_address, port unconditional; ipv6/media_only/tcpo_only/
            // cdn/static/this_port_only on bits 0-5, secret:bytes on bit 10.
            'updateDcOptions' => [
                '_' => 'updateDcOptions',
                'dc_options' => [
                    [
                        '_' => 'dcOption',
                        'flags' => (1 << 0) | (1 << 4),
                        'ipv6' => true,
                        'static' => true,
                        'id' => 2,
                        'ip_address' => '203.0.113.10',
                        'port' => 443,
                    ],
                    [
                        '_' => 'dcOption',
                        'flags' => (1 << 3) | (1 << 10),
                        'cdn' => true,
                        'id' => 4,
                        'ip_address' => '203.0.113.20',
                        'port' => 443,
                        'secret' => 'synthetic-cdn-secret',
                    ],
                ],
            ],

            // channel_id:long messages:Vector<int> pts:int pts_count:int — no flags.
            'updateDeleteChannelMessages' => [
                '_' => 'updateDeleteChannelMessages',
                'channel_id' => 1737482577,
                'messages' => [2401, 2402, 2403],
                'pts' => 1360,
                'pts_count' => 3,
            ],

            // call:InputGroupCall messages:Vector<int> — both unconditional.
            // inputGroupCall carries id:long + access_hash:long, the latter obviously fake.
            'updateDeleteGroupCallMessages' => [
                '_' => 'updateDeleteGroupCallMessages',
                'call' => [
                    '_' => 'inputGroupCall',
                    'id' => 8880003333333,
                    'access_hash' => -1111111111111111111,
                ],
                'messages' => [2404, 2405],
            ],

            // messages:Vector<int> pts:int pts_count:int — superseded by the curated
            // base entry (BASE-WINS), kept here because the D letter owns it.
            'updateDeleteMessages' => [
                '_' => 'updateDeleteMessages',
                'messages' => [2406, 2407],
                'pts' => 1361,
                'pts_count' => 2,
            ],

            // shortcut_id:int — the single unconditional field.
            'updateDeleteQuickReply' => [
                '_' => 'updateDeleteQuickReply',
                'shortcut_id' => 901,
            ],

            // shortcut_id:int messages:Vector<int> — no flags.
            'updateDeleteQuickReplyMessages' => [
                '_' => 'updateDeleteQuickReplyMessages',
                'shortcut_id' => 902,
                'messages' => [2408, 2409],
            ],

            // peer:Peer messages:Vector<int> unconditional; sent_messages on flags.0
            // (deliberately left unset, so the decoder is never asked to read it).
            'updateDeleteScheduledMessages' => [
                '_' => 'updateDeleteScheduledMessages',
                'flags' => 0,
                'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                'messages' => [2410, 2411],
            ],

            // id:int unconditional; filter:DialogFilter on flags.0.
            // dialogFilter: id, title, pinned_peers, include_peers, exclude_peers are
            // unconditional; its optional booleans/string/int all hang off flags bits.
            'updateDialogFilter' => [
                '_' => 'updateDialogFilter',
                'flags' => (1 << 0),
                'id' => 2,
                'filter' => [
                    '_' => 'dialogFilter',
                    'flags' => (1 << 1) | (1 << 3),
                    'non_contacts' => true,
                    'broadcasts' => true,
                    'id' => 2,
                    'title' => [
                        '_' => 'textWithEntities',
                        'text' => 'Fixture filter',
                        'entities' => [],
                    ],
                    'pinned_peers' => [
                        [
                            '_' => 'inputPeerUser',
                            'user_id' => 501558149,
                            'access_hash' => -2222222222222222222,
                        ],
                    ],
                    'include_peers' => [
                        ['_' => 'inputPeerChat', 'chat_id' => 501558150],
                    ],
                    'exclude_peers' => [],
                ],
            ],

            // order:Vector<int> — the single unconditional field.
            'updateDialogFilterOrder' => [
                '_' => 'updateDialogFilterOrder',
                'order' => [2, 3, 1],
            ],

            // A parameterless constructor: the decoded payload is the bare name and
            // nothing else. Nothing is invented to give it a shape.
            'updateDialogFilters' => [
                '_' => 'updateDialogFilters',
            ],

            // peer:DialogPeer unconditional; pinned:true on flags.0, folder_id:int on
            // flags.1. dialogPeer wraps a Peer, so it nests one level deeper.
            'updateDialogPinned' => [
                '_' => 'updateDialogPinned',
                'flags' => (1 << 0) | (1 << 1),
                'pinned' => true,
                'folder_id' => 1,
                'peer' => [
                    '_' => 'dialogPeer',
                    'peer' => ['_' => 'peerChannel', 'channel_id' => 1737482577],
                ],
            ],

            // peer:DialogPeer unconditional; unread:true on flags.0,
            // saved_peer_id:Peer on flags.1.
            'updateDialogUnreadMark' => [
                '_' => 'updateDialogUnreadMark',
                'flags' => (1 << 0) | (1 << 1),
                'unread' => true,
                'peer' => [
                    '_' => 'dialogPeer',
                    'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                ],
                'saved_peer_id' => ['_' => 'peerChat', 'chat_id' => 501558150],
            ],

            // peer:Peer draft:DraftMessage unconditional; top_msg_id:int on flags.0.
            // draftMessage requires only message:string + date:int with flags 0.
            'updateDraftMessage' => [
                '_' => 'updateDraftMessage',
                'flags' => (1 << 0),
                'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                'top_msg_id' => 2412,
                'draft' => [
                    '_' => 'draftMessage',
                    'flags' => 0,
                    'message' => 'Fixture draft',
                    'date' => 1724853100,
                ],
            ],
        ];
    }
}
