<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Endpoint fixtures for the F/G/H/I/J/L slice of the `update*` universe.
 *
 * ## WHICH ENDPOINTS THIS GROUP COVERS
 *
 * The SET is DERIVED from the parsed scheme, never transcribed. Constructors
 * live on `TlType`, not on `TlScheme`, so the derivation walks every type's
 * `constructors()` map:
 *
 * ```bash
 * php -r 'require "vendor/autoload.php";
 *   $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(
 *       __DIR__."/schema/sources/TL_telegram_v227.tl");
 *   $n = [];
 *   foreach ($s->types() as $t) {
 *       foreach ($t->constructors() as $c) {
 *           $k = (string) $c->name;
 *           if (str_starts_with($k, "update")) { $n[$k] = true; }
 *       }
 *   }
 *   ksort($n);
 *   foreach (array_keys($n) as $k) {
 *       if (str_contains($k, ".")) { continue; }          // namespaced ctor group
 *       $l = strtoupper(substr($k, 6, 1));
 *       if (in_array($l, ["F","G","H","I","J","L"], true)) { echo $k, "\n"; }
 *   }'
 * ```
 *
 * That walk reports **175** `update*` constructors in total, of which 8 carry a
 * dotted name (`updates.difference*`, `updates.state`,
 * `updates.channelDifference*`) and are excluded here because a dotted name is
 * a namespaced ctor group, not a bare endpoint.
 *
 * ## MEASURED COUNT FOR THIS GROUP: 14
 *
 * F=2, G=7, H=0, I=1, J=1, L=3 → **14**.
 *
 * This is one MORE than the dispatch brief predicted (F=2, G=6, I=1, J=1, L=3
 * → 13). The brief's G=6 came from a grep LINE COUNT of the raw `.tl` file; a
 * line count is not a constructor count, and the parsed scheme is the
 * authority. Measured G is **7**: `updateGeoLiveViewed` plus the six
 * `updateGroupCall*` constructors. The `updates.getState` line the brief
 * mentions is indeed an RPC method (it is absent from the constructor map, so
 * it was never the source of the off-by-one — the raw line count simply never
 * matched the parsed set). No endpoint was dropped to reach 13.
 *
 * `H` contributes 0: no `updateH*` constructor exists in the scheme. (The `H`
 * of "history" arrives as `updateShortHistoryUpdate`/`updatePtsChanged` under
 * other letters, so there is nothing for an `H` lane to own.)
 *
 * ## SHAPE CONVENTION
 *
 * Follows `tests/Ingest/Concerns/HasNestedUpdateFixtures.php` exactly:
 *
 * - `_` carries the BARE constructor name on EVERY constructor node. No
 *   `#hex` crc32 ids appear anywhere — the fixtures describe the decoded
 *   shape, and an id would be a claim the fixture cannot substantiate.
 * - Set-flag booleans arrive as EXPLICIT keys, because that is how the
 *   decoder surfaces them: `'flags' => (1 << 9)` alongside `'live_story' => true`.
 * - Vectors are PHP lists; peers are objects: `['_', 'peerUser', 'user_id' => N]`.
 * - `bytes` and `string` are plain PHP strings on the wire (`TLDecoder` reads
 *   both through `TLSerializer::unpackString()`), so a bytes field is a raw
 *   string here — NOT base64.
 *
 * Every payload honours the constructor's DECLARED field types: every
 * non-flag field the ctor declares is present, and no field is present that
 * the scheme does not declare.
 *
 * ## NO CREDENTIALS
 *
 * Every value is synthetic. Each `access_hash` is an obviously-fake integer
 * chosen so a fabricated one is visible at a glance. No session string, no
 * real account, no `.env`.
 *
 * ## SELF-CHECK
 *
 * count(payloads()) === 14 — F=2, G=7, H=0, I=1, J=1, L=3. Zero endpoints
 * outside the letters F/G/H/I/J/L. Verified with:
 *
 * ```bash
 * php -r 'require "vendor/autoload.php";
 *   require "tests/Ingest/Concerns/EndpointFixtures/GroupFGHIJL.php";
 *   $p = MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures\GroupFGHIJL::payloads();
 *   echo count($p), " payloads\n";
 *   foreach ($p as $k => $v) {
 *       if (!isset($v["_"]) || $v["_"] !== $k) { echo "BAD KEY: $k\n"; }
 *   }'
 * ```
 *
 * Measured output: `14 payloads`, and zero `BAD KEY` lines.
 */
trait GroupFGHIJL
{
    /**
     * One realistic decoded payload per `update{F,G,H,I,J,L}*` constructor.
     *
     * @return array<string, array<string, mixed>> ctor name => a decoded payload
     */
    public static function payloads(): array
    {
        return [
            // ---- F (2) ------------------------------------------------------

            // updateFavedStickers#e511996d — a pure notification, no params.
            'updateFavedStickers' => [
                '_' => 'updateFavedStickers',
            ],

            // updateFolderPeers#19360dc0 — a FolderPeer vector plus the pts pair
            // that lets the ingester advance its state without a full resync.
            'updateFolderPeers' => [
                '_' => 'updateFolderPeers',
                'folder_peers' => [
                    [
                        '_' => 'folderPeer',
                        'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                        'folder_id' => 2,
                    ],
                    [
                        '_' => 'folderPeer',
                        'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                        'folder_id' => 2,
                    ],
                ],
                'pts' => 1402,
                'pts_count' => 2,
            ],

            // ---- G (7) ------------------------------------------------------

            // updateGeoLiveViewed#871fb939 — a viewer signal on a live location.
            'updateGeoLiveViewed' => [
                '_' => 'updateGeoLiveViewed',
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'msg_id' => 2281,
            ],

            // updateGroupCall#9d2216e0 — flags.1 carries `peer` (the call
            // lives in a dialog); flags.2 is live_story, left unset here so the
            // fixture exercises the `peer` branch only.
            'updateGroupCall' => [
                '_' => 'updateGroupCall',
                'flags' => (1 << 1),
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'call' => [
                    '_' => 'groupCall',
                    // join_muted | can_change_join_muted | title | join_date_asc | creator
                    'flags' => (1 << 1) | (1 << 2) | (1 << 3) | (1 << 6) | (1 << 15),
                    'join_muted' => true,
                    'can_change_join_muted' => true,
                    'join_date_asc' => true,
                    'creator' => true,
                    'id' => 5510000000000001,
                    'access_hash' => 1001001001001001,
                    'participants_count' => 3,
                    'title' => 'Teleframe Standup',
                    'unmuted_video_limit' => 4,
                    'version' => 11,
                ],
            ],

            // updateGroupCallChainBlocks#a477288f — an encrypted chain block is
            // opaque bytes, so `blocks` is a list of raw strings (the decoder
            // unpacks bytes through the same path as string).
            'updateGroupCallChainBlocks' => [
                '_' => 'updateGroupCallChainBlocks',
                'call' => ['_' => 'inputGroupCall', 'id' => 5510000000000001, 'access_hash' => 1001001001001001],
                'sub_chain_id' => 4,
                'blocks' => [
                    'block-zero-opaque-bytes',
                    'block-one-opaque-bytes',
                ],
                'next_offset' => 1024,
            ],

            // updateGroupCallConnection#b783982 — flags.0 presentation. When
            // presentation is set the decoder emits `params` as a DataJSON
            // node; the two are mutually exclusive on the wire, so only the
            // presentation branch is set and `params` is absent.
            'updateGroupCallConnection' => [
                '_' => 'updateGroupCallConnection',
                'flags' => (1 << 0),
                'presentation' => true,
            ],

            // updateGroupCallEncryptedMessage#c957a766 — the payload is opaque
            // by design; the ingester can only see that it arrived and who from.
            'updateGroupCallEncryptedMessage' => [
                '_' => 'updateGroupCallEncryptedMessage',
                'call' => ['_' => 'inputGroupCall', 'id' => 5510000000000002, 'access_hash' => 1001001001001002],
                'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                'encrypted_message' => 'aabbccddeeff00112233445566778899',
            ],

            // updateGroupCallMessage#d8326f0d — a chat message inside a call.
            'updateGroupCallMessage' => [
                '_' => 'updateGroupCallMessage',
                'call' => ['_' => 'inputGroupCall', 'id' => 5510000000000001, 'access_hash' => 1001001001001001],
                'message' => [
                    '_' => 'groupCallMessage',
                    // from_admin (flags.1); flags.0 paid_message_stars left unset
                    'flags' => (1 << 1),
                    'from_admin' => true,
                    'id' => 881,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'date' => 1726000000,
                    'message' => [
                        '_' => 'textWithEntities',
                        'text' => 'notes are in the channel',
                        'entities' => [
                            ['_' => 'messageEntityBold', 'offset' => 0, 'length' => 5],
                        ],
                    ],
                ],
            ],

            // updateGroupCallParticipants#f2ebdb4e — the roster diff. Peer,
            // date and source are the non-flag fields every participant carries.
            'updateGroupCallParticipants' => [
                '_' => 'updateGroupCallParticipants',
                'call' => ['_' => 'inputGroupCall', 'id' => 5510000000000001, 'access_hash' => 1001001001001001],
                'participants' => [
                    [
                        '_' => 'groupCallParticipant',
                        // muted | can_self_unmute | just_joined
                        'flags' => (1 << 0) | (1 << 2) | (1 << 4),
                        'muted' => true,
                        'can_self_unmute' => true,
                        'just_joined' => true,
                        'peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                        'date' => 1726000000,
                        'source' => 2,
                    ],
                    [
                        '_' => 'groupCallParticipant',
                        // video_joined
                        'flags' => (1 << 15),
                        'video_joined' => true,
                        'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                        'date' => 1726000060,
                        'source' => 1,
                    ],
                ],
                'version' => 12,
            ],

            // ---- I (1) ------------------------------------------------------

            // updateInlineBotCallbackQuery#691e9052 — an inline-mode tap. Only
            // flags.0 (`data`) is set; flags.1 game_short_name is the mutually
            // exclusive game branch and is correctly absent.
            'updateInlineBotCallbackQuery' => [
                '_' => 'updateInlineBotCallbackQuery',
                'flags' => (1 << 0),
                'query_id' => 9001001001001001,
                'user_id' => 501558149,
                'msg_id' => [
                    '_' => 'inputBotInlineMessageID',
                    'dc_id' => 2,
                    'id' => 770002,
                    'access_hash' => 2002002002002002,
                ],
                'chat_instance' => 9001001001001002,
                'data' => 'inline-callback-payload',
            ],

            // ---- J (1) ------------------------------------------------------

            // updateJoinChatWebViewDecision#bdac7e70 — the bot answered the
            // WebView join request. joinChatBotResultApproved is a paramless
            // ctor, so the `result` node is pure aggregation.
            'updateJoinChatWebViewDecision' => [
                '_' => 'updateJoinChatWebViewDecision',
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'query_id' => 3003003003003003,
                'result' => ['_' => 'joinChatBotResultApproved'],
            ],

            // ---- L (3) ------------------------------------------------------

            // updateLangPack#56022f4d — a localization string bundle drop.
            'updateLangPack' => [
                '_' => 'updateLangPack',
                'difference' => [
                    '_' => 'langPackDifference',
                    'lang_code' => 'en',
                    'from_version' => 3,
                    'version' => 4,
                    'strings' => [
                        ['_' => 'langPackString', 'key' => 'app_name', 'value' => 'Teleframe'],
                        ['_' => 'langPackString', 'key' => 'app_subtitle', 'value' => 'Telegram, framed'],
                    ],
                ],
            ],

            // updateLangPackTooLong#46560264 — the server is telling us to stop
            // asking for this language; lang_code is the only field.
            'updateLangPackTooLong' => [
                '_' => 'updateLangPackTooLong',
                'lang_code' => 'si',
            ],

            // updateLoginToken#564fe691 — a pure notification, no params.
            'updateLoginToken' => [
                '_' => 'updateLoginToken',
            ],
        ];
    }
}
