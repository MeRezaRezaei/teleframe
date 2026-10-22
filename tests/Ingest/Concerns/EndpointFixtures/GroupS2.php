<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Realistic decoded payloads for the ODD-INDEX HALF of the layer-227 MTProto
 * endpoints whose constructor name has `S` at index 6 — the first character
 * after the literal prefix `update`, uppercased.
 *
 * `S` is the single largest letter in the universe (33 endpoints) and is being
 * handled by TWO sibling lanes in parallel: this file is `GroupS2`, the other
 * is `GroupS1`. The split is by INDEX PARITY of the sorted S list, not by any
 * semantic grouping, and that is deliberate: parity guarantees the two payload
 * sets are DISJOINT without either lane needing to know what the other is
 * doing. This lane takes the ODD indices (1st, 3rd, 5th, …); `S1` takes the
 * even ones. BOTH lanes declare `letters() === ['S']` — two groups claiming the
 * same LETTER is fine (the loader keys ownership by CTOR, not by letter), while
 * a duplicate CTOR KEY is a hard failure, so the sets must not overlap.
 *
 * MEASURED COUNT: 16 endpoints, of TOTAL=33 for the letter.
 *
 * Command that produced the set (parsed constructors, NOT a grep — a text count
 * also matches RPC METHODS such as `updates.getDifference`; see
 * .brain/decisions/D-016-endpoint-universe-175-and-three-missing-apis.md), run
 * from the repo root:
 *
 *     cd /home/me/Documents/projects/teleframe && timeout -k 5 180 php -r 'require "vendor/autoload.php";
 *       $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(__DIR__."/schema/sources/TL_telegram_v227.tl");
 *       $n = []; foreach ($s->types() as $t) { foreach (array_keys((array) $t->constructors()) as $k) { $n[(string)$k] = true; } }
 *       $u = array_values(array_filter(array_keys($n), fn($k) => str_starts_with($k, "update")));
 *       sort($u);
 *       $s = array_values(array_filter($u, fn($k) => strtoupper(substr($k,6,1)) === "S"));
 *       echo "TOTAL=", count($s), "\n";
 *       foreach ($s as $i => $k) { if ($i % 2 === 1) echo $i, " ", $k, "\n"; }'
 *
 * The odd-index rule applied on the LAST line (`$i % 2 === 1`) is what makes
 * this set disjoint from `GroupS1`'s. Its verbatim output:
 *
 *     TOTAL=33
 *     1 updateSavedGifs
 *     3 updateSavedRingtones
 *     5 updateSentStoryReaction
 *     7 updateShort
 *     9 updateShortMessage
 *     11 updateSmsJob
 *     13 updateStarGiftAuctionUserState
 *     15 updateStarsBalance
 *     17 updateStickerSets
 *     19 updateStoriesStealthMode
 *     21 updateStoryID
 *     23 updates.channelDifference
 *     25 updates.channelDifferenceTooLong
 *     27 updates.differenceEmpty
 *     29 updates.differenceTooLong
 *     31 updatesCombined
 *
 * Note the three `updates.*` result constructors and `updatesCombined` qualify
 * for this letter legitimately: index 6 of `updates.channelDifference` is the
 * `s` of `updates`, so the letter rule captures them without special-casing.
 *
 * Declared fields were read out of `schema/sources/TL_telegram_v227.tl`
 * (`grep -n "^updateS\|^updateStickerSets\|^updatesCombined\|^updates\." …`,
 * lines 346-488 and 759-761) plus the nested constructors this group touches
 * (`starsAmount#bbb6b4a3`, `starGiftAuctionUserState#2eeed1c4`,
 * `storiesStealthMode#712e27fd`, `reactionEmoji#1b2286b8`,
 * `messageEntityBold#bd610bc9`, `dialogPeer#…`) so each payload matches what the
 * decoder will actually demand.
 *
 * SHAPE CONVENTION (mirrors tests/Ingest/Concerns/HasNestedUpdateFixtures.php,
 * and is what Loader::assertWireShaped enforces):
 *   - `_` carries the BARE constructor name on EVERY constructor node, root and
 *     nested alike. No `#hex` ids anywhere — that is the TL DECLARATION, not the
 *     decoded payload.
 *   - set-flag booleans arrive as EXPLICIT keys beside the `flags` bitmask that
 *     turns them on (`'flags' => (1 << 0), 'masks' => true`).
 *   - vectors are PHP lists; peers are objects (`peerUser#user_id`,
 *     `peerChannel#channel_id`, `peerChat#chat_id`) and DialogPeers are objects
 *     too (`dialogPeer` wraps a `Peer`).
 *   - Only fields the constructor DECLARES appear. A `flags`-conditioned field is
 *     present only when its bit IS set in the mask supplied here, so the decoder
 *     is never asked to read a field the wire would not have sent. Nothing is
 *     invented to fill a shape.
 *   - Every value is SYNTHETIC. No session string, no credential, nothing read
 *     from `.env`.
 *   - Ids are distinct per endpoint so nothing collides in the peer-scoped key
 *     (account_id, peer_type, peer_id, id) — see
 *     tests/Mirror/MessagePeerScopedPrimaryKeyTest.php.
 *
 * SELF-CHECK: `count(self::payloads()) === 16`, and every key satisfies
 * `strtoupper(substr($key, 6, 1)) === 'S'` — zero endpoints outside the letter.
 */
trait GroupS2
{
    /**
     * The letters this group owns, UPPERCASE — the character at index 6 of each
     * constructor name, after the `update` prefix.
     *
     * `S1` declares the same letter on purpose; see the class docblock.
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
            // ---------------------------------------------------------------
            // Odd indices 1-11
            // ---------------------------------------------------------------

            // updateSavedGifs = Update; — declares NO fields at all.
            'updateSavedGifs' => [
                '_' => 'updateSavedGifs',
            ],

            // updateSavedRingtones = Update; — declares NO fields at all.
            'updateSavedRingtones' => [
                '_' => 'updateSavedRingtones',
            ],

            // peer:Peer story_id:int reaction:Reaction — all three unconditional.
            'updateSentStoryReaction' => [
                '_' => 'updateSentStoryReaction',
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'story_id' => 61,
                'reaction' => ['_' => 'reactionEmoji', 'emoticon' => '🔥'],
            ],

            // update:Update date:int — updatePtsChanged is a fieldless Update, so
            // the nested node needs nothing beyond its own bare name.
            'updateShort' => [
                '_' => 'updateShort',
                'update' => ['_' => 'updatePtsChanged'],
                'date' => 1724853100,
            ],

            // out:flags.1, mentioned:flags.4, media_unread:flags.5 deliberately set;
            // silent:flags.13, fwd_from:flags.2, via_bot_id:flags.11, reply_to:flags.3,
            // entities:flags.7, ttl_period:flags.25 deliberately LEFT unset.
            'updateShortMessage' => [
                '_' => 'updateShortMessage',
                'flags' => (1 << 1) | (1 << 4) | (1 << 5),
                'out' => true,
                'mentioned' => true,
                'media_unread' => true,
                'id' => 6001,
                'user_id' => 501558149,
                'message' => 'Short fixture message',
                'pts' => 2151,
                'pts_count' => 1,
                'date' => 1724853200,
            ],

            // job_id:string — the single unconditional field.
            'updateSmsJob' => [
                '_' => 'updateSmsJob',
                'job_id' => 'synthetic-sms-job-0001',
            ],

            // gift_id:long user_state:StarGiftAuctionUserState. In the nested ctor the
            // bid_* quartet hangs off flags.0 and returned:bool off flags.1.
            'updateStarGiftAuctionUserState' => [
                '_' => 'updateStarGiftAuctionUserState',
                'gift_id' => 8800112233445567,
                'user_state' => [
                    '_' => 'starGiftAuctionUserState',
                    'flags' => (1 << 0) | (1 << 1),
                    'returned' => true,
                    'bid_amount' => 1230000000000,
                    'bid_date' => 1724853300,
                    'min_bid_amount' => 500000000000,
                    'bid_peer' => ['_' => 'peerUser', 'user_id' => 501558149],
                    'acquired_count' => 2,
                ],
            ],

            // balance:StarsAmount — amount:long and nanos:int are both unconditional.
            'updateStarsBalance' => [
                '_' => 'updateStarsBalance',
                'balance' => ['_' => 'starsAmount', 'amount' => 424242, 'nanos' => 500000000],
            ],

            // masks:flags.0?true emojis:flags.1?true — both turned on, nothing else.
            'updateStickerSets' => [
                '_' => 'updateStickerSets',
                'flags' => (1 << 0) | (1 << 1),
                'masks' => true,
                'emojis' => true,
            ],

            // stealth_mode:StoriesStealthMode — active_until_date on flags.0,
            // cooldown_until_date on flags.1, both present because both bits are set.
            'updateStoriesStealthMode' => [
                '_' => 'updateStoriesStealthMode',
                'stealth_mode' => [
                    '_' => 'storiesStealthMode',
                    'flags' => (1 << 0) | (1 << 1),
                    'active_until_date' => 1724939500,
                    'cooldown_until_date' => 1725025900,
                ],
            ],

            // id:int random_id:long — both unconditional.
            'updateStoryID' => [
                '_' => 'updateStoryID',
                'id' => 62,
                'random_id' => 8880003333333333,
            ],

            // ---------------------------------------------------------------
            // Odd indices 23-31
            // ---------------------------------------------------------------

            // pts:int unconditional; final:flags.0 and timeout:flags.1 set here.
            //
            // `other_updates` carries ONE real update, deliberately NOT empty.
            // This ctor is an `update-carrier` (it declares
            // `other_updates:Vector<Update>`), and the carrier obligation is that
            // each member of that vector is PLACED. An empty vector would make
            // that obligation unobservable — the gate would pass whether the
            // unwrap works or the whole payload is dropped — which is the
            // vacuous green this fixture exists to prevent. CORRECTED 2026-10-05
            // on `channelDifference`: it was `[]`.
            'updates.channelDifference' => [
                '_' => 'updates.channelDifference',
                'flags' => (1 << 0),
                'final' => true,
                'pts' => 2161,
                'new_messages' => [],
                'other_updates' => [
                    ['_' => 'updatePinnedDialogs', 'flags' => 0],
                ],
                'chats' => [],
                'users' => [],
            ],

            // timeout:flags.1 set; final:flags.0 deliberately left unset. dialog:Dialog
            // wraps a Peer, so the nested node is an OBJECT, not a list element.
            'updates.channelDifferenceTooLong' => [
                '_' => 'updates.channelDifferenceTooLong',
                'flags' => (1 << 1),
                'timeout' => 60,
                'dialog' => [
                    '_' => 'dialogPeer',
                    'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                ],
                'messages' => [],
                'chats' => [],
                'users' => [],
            ],

            // date:int seq:int — both unconditional, no flags mask at all.
            'updates.differenceEmpty' => [
                '_' => 'updates.differenceEmpty',
                'date' => 1724853400,
                'seq' => 0,
            ],

            // pts:int — the single unconditional field.
            'updates.differenceTooLong' => [
                '_' => 'updates.differenceTooLong',
                'pts' => 2162,
            ],

            // updates/users/chats vectors plus date/seq_start/seq. The nested Update is
            // `updatePtsChanged`, which declares no fields, so it stays a bare node.
            'updatesCombined' => [
                '_' => 'updatesCombined',
                'updates' => [
                    ['_' => 'updatePtsChanged'],
                ],
                'users' => [],
                'chats' => [],
                'date' => 1724853500,
                'seq_start' => 0,
                'seq' => 1,
            ],
        ];
    }
}
