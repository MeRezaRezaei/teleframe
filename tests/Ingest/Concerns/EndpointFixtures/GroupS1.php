<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Realistic decoded payloads for HALF the layer-227 MTProto endpoints whose
 * constructor name has `S` at index 6 — the EVEN-index half.
 *
 * ## WHY HALF, AND WHY BY PARITY
 *
 * `S` is the single largest letter in the endpoint universe (33 endpoints), so
 * it is handled by TWO sibling lanes in parallel: this one (`S1`) and `S2`. The
 * division is by INDEX PARITY of the sorted `S` list — `S1` takes indices 0, 2,
 * 4, … and `S2` takes 1, 3, 5, … — which guarantees the two payload sets are
 * DISJOINT without either lane having to know what the other is producing.
 * Parity is a set operation on a sorted list, so it cannot drift and cannot
 * collide: an endpoint belongs to exactly one of the two by construction.
 *
 * Both lanes declare `letters() === ['S']`. That is expected and correct — the
 * loader treats a DUPLICATE CTOR KEY from two groups as a hard failure, but two
 * groups both CLAIMING the letter is not a duplicate, it is the parity split.
 * A duplicate-ctor fatal therefore remains a real signal here: it can only mean
 * one lane shipped an odd index.
 *
 * ## MEASUREMENT — the slice, derived, never transcribed
 *
 * The list below was derived by PARSING the layer-227 TL (a grep also matches
 * RPC methods; see `.brain/decisions/D-016-endpoint-universe-175-and-three-missing-apis.md`).
 * Command actually run, from the repo root:
 *
 *     timeout -k 5 180 php -r 'require "vendor/autoload.php";
 *       $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(__DIR__."/schema/sources/TL_telegram_v227.tl");
 *       $n = []; foreach ($s->types() as $t) { foreach (array_keys((array) $t->constructors()) as $k) { $n[(string)$k] = true; } }
 *       $u = array_values(array_filter(array_keys($n), fn($k) => str_starts_with($k, "update")));
 *       sort($u);
 *       $s = array_values(array_filter($u, fn($k) => strtoupper(substr($k,6,1)) === "S"));
 *       echo "TOTAL=", count($s), "\n";
 *       foreach ($s as $i => $k) { if ($i % 2 === 0) echo $i, " ", $k, "\n"; }'
 *
 * Verbatim output (TOTAL=33, then every EVEN index — the rule applied is
 * `$i % 2 === 0`):
 *
 *     TOTAL=33
 *     0 updateSavedDialogPinned
 *     2 updateSavedReactionTags
 *     4 updateSentPhoneCode
 *     6 updateServiceNotification
 *     8 updateShortChatMessage
 *     10 updateShortSentMessage
 *     12 updateStarGiftAuctionState
 *     14 updateStarGiftCraftFail
 *     16 updateStarsRevenueStatus
 *     18 updateStickerSetsOrder
 *     20 updateStory
 *     22 updates
 *     24 updates.channelDifferenceEmpty
 *     26 updates.difference
 *     28 updates.differenceSlice
 *     30 updates.state
 *     32 updatesTooLong
 *
 * So `S` splits 33 = 17 (even, here) + 16 (odd, `S2`). No count in this file
 * was copied from a plan: 33 and 17 are read off the output above.
 *
 * ## SHAPE CONVENTION — mirrors tests/Ingest/Concerns/HasNestedUpdateFixtures.php
 *
 *   - `_` is the BARE constructor name on EVERY constructor node. No `#hex` ids
 *     anywhere — the loader raises on one.
 *   - Set-flag booleans arrive as EXPLICIT keys alongside the `flags` mask that
 *     turns them on.
 *   - Vectors are PHP lists; peers are objects (`peerUser#user_id` /
 *     `peerChannel#channel_id` / `peerChat#chat_id`).
 *   - Only fields the ctor actually DECLARES appear. A `flags`-conditioned field
 *     is present only when its bit IS set in the mask, so the decoder is never
 *     asked to read a field the wire would not have sent. Each payload's comment
 *     names the declared block it was read from.
 *   - Every value is SYNTHETIC. `access_hash` values are obviously-fake repeated
 *     digits; the `phone_code_hash` is an obviously-fake non-hex string. No
 *     session strings, no credentials, nothing read from `.env`.
 *   - Ids are distinct per endpoint so nothing collides in the peer-scoped key
 *     (account_id, peer_type, peer_id, id) — see
 *     tests/Mirror/MessagePeerScopedPrimaryKeyTest.php.
 *
 * ## OWNED BUT NOT PROVIDED
 *
 * None. Every even index the measurement printed above is present below.
 */
trait GroupS1
{
    /**
     * The index-6 letters this group owns.
     *
     * The whole letter `S` — `S2` claims the same letter by design, and the
     * loader keys ownership off the CTOR, not off the letter, so two groups
     * claiming `S` is not a collision.
     *
     * @return list<string>
     */
    public static function letters(): array
    {
        return ['S'];
    }

    /**
     * @return array<string, array<string, mixed>> ctor name => a decoded payload
     */
    public static function payloads(): array
    {
        return [
            // index 0 — `updateSavedDialogPinned#aeaf9e74 flags:# pinned:flags.0?true peer:DialogPeer`
            // flags.0 set, so `pinned` is present as an explicit key.
            'updateSavedDialogPinned' => [
                '_' => 'updateSavedDialogPinned',
                'flags' => 1,
                'pinned' => true,
                'peer' => ['_' => 'dialogPeer', 'peer' => ['_' => 'peerUser', 'user_id' => 515020001]],
            ],

            // index 2 — `updateSavedReactionTags#39c67432 = Update;` — no fields at all,
            // so the root node carries nothing but its own name.
            'updateSavedReactionTags' => [
                '_' => 'updateSavedReactionTags',
            ],

            // index 4 — `updateSentPhoneCode#504aa18f sent_code:auth.SentCode`
            // auth.sentCode#5e002502 flags:# type:auth.SentCodeType phone_code_hash:string
            //   next_type:flags.1?auth.CodeType timeout:flags.2?int — mask 0, so neither
            // optional field appears.
            'updateSentPhoneCode' => [
                '_' => 'updateSentPhoneCode',
                'sent_code' => [
                    '_' => 'auth.sentCode',
                    'flags' => 0,
                    'type' => ['_' => 'auth.sentCodeTypeSms', 'length' => 5],
                    'phone_code_hash' => 'fixture-phone-code-hash-not-a-real-hash',
                ],
            ],

            // index 6 — `updateServiceNotification#ebe46819 flags:# popup:flags.0?true
            //   invert_media:flags.2?true inbox_date:flags.1?int type:string message:string
            //   media:MessageMedia entities:Vector<MessageEntity>`
            // mask = popup (bit 0) | inbox_date (bit 1); invert_media (bit 2) left unset,
            // so no `invert_media` key.
            'updateServiceNotification' => [
                '_' => 'updateServiceNotification',
                'flags' => 3,
                'popup' => true,
                'inbox_date' => 5150206,
                'type' => 'updateServiceNotification',
                'message' => 'Fixture service notification body — synthetic, no live content.',
                'media' => ['_' => 'messageMediaEmpty'],
                'entities' => [
                    ['_' => 'messageEntityBold', 'offset' => 0, 'length' => 7],
                ],
            ],

            // index 8 — `updateShortChatMessage#4d6deea5 flags:# out:flags.1?true
            //   mentioned:flags.4?true media_unread:flags.5?true silent:flags.13?true
            //   id:int from_id:long chat_id:long message:string pts:int pts_count:int date:int
            //   fwd_from:flags.2?MessageFwdHeader via_bot_id:flags.11?long
            //   reply_to:flags.3?MessageReplyHeader entities:flags.7?Vector<MessageEntity>
            //   ttl_period:flags.25?int = Updates;`
            // mask = out (1) | fwd_from (2) | entities (7) | via_bot_id (11) = 2+4+128+2048.
            // mentioned / media_unread / reply_to / silent / ttl_period stay unset and absent.
            'updateShortChatMessage' => [
                '_' => 'updateShortChatMessage',
                'flags' => 2182,
                'out' => true,
                'id' => 5150208,
                'from_id' => 515020801,
                'chat_id' => 515020802,
                'message' => 'Fixture short-chat message body.',
                'pts' => 515,
                'pts_count' => 1,
                'date' => 5150209,
                'fwd_from' => [
                    '_' => 'messageFwdHeader',
                    'flags' => 1,
                    'from_id' => ['_' => 'peerUser', 'user_id' => 515020803],
                    'date' => 5150210,
                ],
                'via_bot_id' => 515020804,
                'entities' => [
                    ['_' => 'messageEntityItalic', 'offset' => 8, 'length' => 5],
                ],
            ],

            // index 10 — `updateShortSentMessage#9015e101 flags:# out:flags.1?true
            //   id:int pts:int pts_count:int date:int media:flags.9?MessageMedia
            //   entities:flags.7?Vector<MessageEntity> ttl_period:flags.25?int = Updates;`
            // mask = out (1) | entities (7) | media (9) = 2+128+512.
            'updateShortSentMessage' => [
                '_' => 'updateShortSentMessage',
                'flags' => 642,
                'out' => true,
                'id' => 5150210,
                'pts' => 520,
                'pts_count' => 1,
                'date' => 5150211,
                'media' => ['_' => 'messageMediaEmpty'],
                'entities' => [
                    ['_' => 'messageEntityCode', 'offset' => 0, 'length' => 4],
                ],
            ],

            // index 12 — `updateStarGiftAuctionState#48e246c2 gift_id:long state:StarGiftAuctionState`
            // starGiftAuctionState#771a4e66 has no flags: version/start_date/end_date,
            // min_bid_amount, bid_levels, top_bidders, next_round_at, last_gift_num,
            // gifts_left, current_round, total_rounds, rounds — every one declared.
            'updateStarGiftAuctionState' => [
                '_' => 'updateStarGiftAuctionState',
                'gift_id' => 5150212,
                'state' => [
                    '_' => 'starGiftAuctionState',
                    'version' => 1,
                    'start_date' => 5150213,
                    'end_date' => 5150214,
                    'min_bid_amount' => 5150215,
                    'bid_levels' => [
                        ['_' => 'auctionBidLevel', 'pos' => 1, 'amount' => 5150216, 'date' => 5150217],
                    ],
                    'top_bidders' => [5150218],
                    'next_round_at' => 5150219,
                    'last_gift_num' => 22,
                    'gifts_left' => 7,
                    'current_round' => 2,
                    'total_rounds' => 5,
                    'rounds' => [
                        ['_' => 'starGiftAuctionRound', 'num' => 1, 'duration' => 3600],
                    ],
                ],
            ],

            // index 14 — `updateStarGiftCraftFail#ac072444 = Update;` — no fields.
            'updateStarGiftCraftFail' => [
                '_' => 'updateStarGiftCraftFail',
            ],

            // index 16 — `updateStarsRevenueStatus#a584b019 peer:Peer status:StarsRevenueStatus`
            // starsRevenueStatus#febe5491 flags:# withdrawal_enabled:flags.0?true
            //   current_balance:StarsAmount available_balance:StarsAmount
            //   overall_revenue:StarsAmount next_withdrawal_at:flags.1?int
            // mask = withdrawal_enabled (0) | next_withdrawal_at (1) = 3.
            'updateStarsRevenueStatus' => [
                '_' => 'updateStarsRevenueStatus',
                'peer' => ['_' => 'peerUser', 'user_id' => 5150220],
                'status' => [
                    '_' => 'starsRevenueStatus',
                    'flags' => 3,
                    'withdrawal_enabled' => true,
                    'current_balance' => ['_' => 'starsAmount', 'amount' => 5150221, 'nanos' => 500000000],
                    'available_balance' => ['_' => 'starsAmount', 'amount' => 5150222, 'nanos' => 0],
                    'overall_revenue' => ['_' => 'starsAmount', 'amount' => 5150223, 'nanos' => 250000000],
                    'next_withdrawal_at' => 5150224,
                ],
            ],

            // index 18 — `updateStickerSetsOrder#bb2d201 flags:# masks:flags.0?true
            //   emojis:flags.1?true order:Vector<long>`
            // mask = masks (0) | emojis (1) = 3; `order` is unconditional.
            'updateStickerSetsOrder' => [
                '_' => 'updateStickerSetsOrder',
                'flags' => 3,
                'masks' => true,
                'emojis' => true,
                'order' => [5150225, 5150226],
            ],

            // index 20 — `updateStory#75b3b798 peer:Peer story:StoryItem`
            // storyItem#16a4b93c flags:# … id:int date:int from_id:flags.18?Peer
            //   fwd_from:flags.17?StoryFwdHeader expire_date:int caption:flags.0?string
            //   entities:flags.1?Vector<MessageEntity> media:MessageMedia …`
            // mask = caption (0) | entities (1) = 3; from_id / fwd_from and the rest
            // stay unset. id, date, expire_date and media are unconditional.
            'updateStory' => [
                '_' => 'updateStory',
                'peer' => ['_' => 'peerChannel', 'channel_id' => 5150227],
                'story' => [
                    '_' => 'storyItem',
                    'flags' => 3,
                    'id' => 5150228,
                    'date' => 5150229,
                    'expire_date' => 5150230,
                    'caption' => 'Fixture story caption.',
                    'entities' => [
                        ['_' => 'messageEntityUnderline', 'offset' => 0, 'length' => 6],
                    ],
                    'media' => ['_' => 'messageMediaEmpty'],
                ],
            ],

            // index 22 — `updates#74ae4240 updates:Vector<Update> users:Vector<User>
            //   chats:Vector<Chat> date:int seq:int = Updates;`
            // The nested `updateChannelTooLong#108d941f flags:# channel_id:long pts:flags.0?int`
            // carries mask 0, so its optional `pts` is absent.
            'updates' => [
                '_' => 'updates',
                'updates' => [
                    ['_' => 'updateChannelTooLong', 'flags' => 0, 'channel_id' => 5150231],
                ],
                'users' => [
                    ['_' => 'userEmpty', 'id' => 5150232],
                ],
                'chats' => [
                    ['_' => 'chatEmpty', 'id' => 5150233],
                ],
                'date' => 5150234,
                'seq' => 515,
            ],

            // index 24 — `updates.channelDifferenceEmpty#3e11affb flags:# final:flags.0?true
            //   pts:int timeout:flags.1?int = updates.ChannelDifference;`
            // mask = final (0) | timeout (1) = 3.
            'updates.channelDifferenceEmpty' => [
                '_' => 'updates.channelDifferenceEmpty',
                'flags' => 3,
                'final' => true,
                'pts' => 530,
                'timeout' => 5150235,
            ],

            // index 26 — `updates.difference#f49ca0 new_messages:Vector<Message>
            //   new_encrypted_messages:Vector<EncryptedMessage> other_updates:Vector<Update>
            //   chats:Vector<Chat> users:Vector<User> state:updates.State`
            // The two message vectors are declared but carry no fixture rows: a
            // `message` payload is a large unrelated type, and an empty list is
            // what a real difference with no new messages looks like.
            'updates.difference' => [
                '_' => 'updates.difference',
                'new_messages' => [],
                'new_encrypted_messages' => [],
                'other_updates' => [
                    ['_' => 'updateChannel', 'channel_id' => 5150236],
                ],
                'chats' => [
                    ['_' => 'chatEmpty', 'id' => 5150237],
                ],
                'users' => [
                    ['_' => 'userEmpty', 'id' => 5150238],
                ],
                'state' => [
                    '_' => 'updates.state',
                    'pts' => 535,
                    'qts' => 536,
                    'date' => 5150239,
                    'seq' => 535,
                    'unread_count' => 4,
                ],
            ],

            // index 28 — `updates.differenceSlice#a8fb1981 new_messages:Vector<Message>
            //   new_encrypted_messages:Vector<EncryptedMessage> other_updates:Vector<Update>
            //   chats:Vector<Chat> users:Vector<User> intermediate_state:updates.State`
            // Same shape as `updates.difference` except the state key is
            // `intermediate_state`, so it is absent from that sibling's payload.
            'updates.differenceSlice' => [
                '_' => 'updates.differenceSlice',
                'new_messages' => [],
                'new_encrypted_messages' => [],
                'other_updates' => [
                    ['_' => 'updateChannelTooLong', 'flags' => 0, 'channel_id' => 5150240],
                ],
                'chats' => [
                    ['_' => 'chatEmpty', 'id' => 5150241],
                ],
                'users' => [
                    ['_' => 'userEmpty', 'id' => 5150242],
                ],
                'intermediate_state' => [
                    '_' => 'updates.state',
                    'pts' => 540,
                    'qts' => 541,
                    'date' => 5150243,
                    'seq' => 540,
                    'unread_count' => 6,
                ],
            ],

            // index 30 — `updates.state#a56c2a3e pts:int qts:int date:int seq:int
            //   unread_count:int = updates.State;` — no flags, every field present.
            'updates.state' => [
                '_' => 'updates.state',
                'pts' => 545,
                'qts' => 546,
                'date' => 5150244,
                'seq' => 545,
                'unread_count' => 9,
            ],

            // index 32 — `updatesTooLong#e317af7e = Updates;` — no fields.
            'updatesTooLong' => [
                '_' => 'updatesTooLong',
            ],
        ];
    }
}
