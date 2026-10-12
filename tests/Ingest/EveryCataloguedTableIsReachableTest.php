<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Laravel\MirrorIngesterFactory;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\EndpointCoverage;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSource;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use PHPUnit\Framework\TestCase;

/**
 * EVERY catalogued table must be reachable, or explicitly excused with a reason.
 *
 * ## The defect this gate exists to stop
 *
 * `MirrorNestedSourceWalker::walk()` iterates ONLY
 * `$this->catalog->nestedSourcesFor($ctor)` — it never infers an edge. A
 * catalogued table with no declared `sources` edge can therefore never be
 * reached from ANY RPC result or update: its Telegram facts are unrecoverable,
 * with no error anywhere. Measured 2026-10-02 at `2fb2d3da`: **18 of 36**
 * catalogued tables had no edge, including `tf_documents`, `tf_photos`,
 * `tf_web_pages`, `tf_messages`, `tf_message_entities`, `tf_reactions`,
 * `tf_message_actions` and `tf_folders`. That is a frozen measurement of one
 * commit, left as measured. `tf_message_entities` was DELETED from the catalog
 * on 2026-10-04 — nothing ever wrote it, and its entities already ship stored
 * without loss in the positioned sibling `tf_messages_entities` — so it is
 * named here only inside that historical count and is no longer an excused
 * table below.
 *
 * The absence is silent in the worst way: `tf_messages` filled while
 * `tf_documents`, `tf_photos` and `tf_web_pages` sat at zero rows, which reads
 * as "this account sent no documents" rather than "this mirror never looked".
 *
 * ## Why "has a `sources` key" is NOT the assertion
 *
 * A declared edge is necessary and NOT sufficient. `message.media` needed
 * THREE changes to actually place a row — the edge, a descent that enters the
 * node, and a fillable key — and two of them were invisible because all 21
 * shipped parents were top-level, so no fixture exercised the descent branch.
 * A table can also carry an edge that reaches nothing (`tf_reactions`:
 * `reactionCount.reaction` validates, but `MessageReactions` is not a declared
 * parent type, so the walk never stands on `reactionCount` from a `message`).
 *
 * So this gate asks the STRONGER question, and asks it of the real artifacts:
 * **is this table's edge reachable from a realistic decoded payload?** It
 * walks the REAL `MirrorNestedSourceWalker` over a `REACHABLE` map of payloads
 * shaped as the wire decodes them, and demands the table appear in the facts
 * some envelope yields. A table that cannot is either fixed or listed in
 * `UNREACHABLE` with a reason that names the mechanism.
 *
 * An excuse must name the reason. "We could not think of one" is not a reason,
 * and neither is "the validator rejected it" — the validator rejecting an edge
 * says the edge was wrong, not that the table is unreachable.
 */
final class EveryCataloguedTableIsReachableTest extends TestCase
{
    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    /**
     * Realistic decoded payloads, one per ENVELOPE the mirror is actually fed.
     *
     * These are shaped as the wire decodes them — `_` set, vectors as lists,
     * peers as `peerChannel#channel_id` objects — because a reachability probe
     * built from a convenient shape proves nothing about the shape that
     * arrives. Every envelope here is one the ingest seams genuinely receive:
     * the first five are `Update` constructors off the Redis bus, the rest are
     * decoded RPC results (`MirrorResultIngester`).
     *
     * The values are all SYNTHETIC. No session string, no `access_hash` from a
     * real account, no database password appears in this file or any fixture
     * derived from it — `access_hash` here is a made-up number standing in for
     * a value whose COLUMN is legitimately named `access_hash`.
     *
     * @var array<string, array<string, mixed>>
     */
    private const REACHABLE = [
        // ── the update bus: newMessage / newChannelMessage / editMessage ──
        'updateNewMessage+photo' => [
            '_' => 'updateNewMessage', 'pts' => 41, 'pts_count' => 1,
            'message' => ['_' => 'message', 'id' => 101, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000, 'message' => 'photo post',
                'media' => ['_' => 'messageMediaPhoto', 'photo' => ['_' => 'photo', 'id' => 9001,
                    'access_hash' => 111222333444555666, 'file_reference' => "\x01\x02\x03\x04",
                    'date' => 1700000000, 'dc_id' => 2, 'has_stickers' => false,
                    'sizes' => [['_' => 'photoSize', 'type' => 'x', 'w' => 90, 'h' => 90, 'size' => 1234]]]]],
        ],
        'updateNewMessage+document' => [
            '_' => 'updateNewMessage', 'pts' => 42, 'pts_count' => 1,
            'message' => ['_' => 'message', 'id' => 102, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000, 'message' => 'doc post',
                'media' => ['_' => 'messageMediaDocument', 'document' => ['_' => 'document', 'id' => 9002,
                    'access_hash' => 777888999000111222, 'file_reference' => "\x05\x06\x07\x08",
                    'date' => 1700000000, 'mime_type' => 'application/pdf', 'size' => 40960, 'dc_id' => 4]]],
        ],
        'updateNewMessage+webpage' => [
            '_' => 'updateNewMessage', 'pts' => 43, 'pts_count' => 1,
            'message' => ['_' => 'message', 'id' => 103, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000, 'message' => 'link post',
                'media' => ['_' => 'messageMediaWebPage', 'webpage' => ['_' => 'webPage', 'id' => 9003,
                    'date' => 1700000000, 'url' => 'https://example.invalid/a-post',
                    'site_name' => 'Example', 'type' => 'simple']]],
        ],
        'updateNewMessage+serviceAction' => [
            '_' => 'updateNewMessage', 'pts' => 44, 'pts_count' => 1,
            'message' => ['_' => 'messageService', 'id' => 104, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000,
                'action' => ['_' => 'messageActionChatCreate', 'title' => 'New Group', 'users' => []]],
        ],
        'updateNewMessage+todo' => [
            '_' => 'updateNewMessage', 'pts' => 45, 'pts_count' => 1,
            'message' => ['_' => 'message', 'id' => 105, 'peer_id' => ['_' => 'peerChat', 'chat_id' => 7],
                'date' => 1700000000,
                'media' => ['_' => 'messageMediaToDo', 'todo' => ['_' => 'todoList', 'id' => 9006,
                    'list' => [['_' => 'todoItem', 'id' => 9007, 'title' => 'buy milk', 'completed' => false]]]]],
        ],
        'updateGroupCall' => [
            '_' => 'updateGroupCall', 'call' => ['_' => 'groupCall', 'id' => 9008,
                'access_hash' => 333444555666777888, 'date' => 1700000000, 'duration' => 60,
                'participants' => []], 'date' => 1700000000,
        ],
        // ── the update bus: the sixteen edges that shipped before this task ──
        // These envelopes are NOT decoration. The gate demands that EVERY
        // declared edge is walked by some realistic payload, so a shipped edge
        // whose envelope is missing here reads as an edge that reaches nothing —
        // which is the exact failure this task exists to end, and it is why
        // `tf_reactions` is excused below rather than quietly tolerated.
        'updateChannelParticipant' => [
            '_' => 'updateChannelParticipant', 'via_chatlist' => false, 'channel_id' => 555000111,
            'date' => 1700000000, 'actor_id' => 7, 'user_id' => 7, 'qts' => 5,
            'new_participant' => ['_' => 'channelParticipant', 'user_id' => 7,
                'date' => 1700000000, 'subscription_until_date' => 1800000000],
        ],
        'updateEncryption' => [
            '_' => 'updateEncryption', 'date' => 1700000000,
            'chat' => ['_' => 'encryptedChat', 'id' => 9020, 'access_hash' => 555666777888999000,
                'date' => 1700000000, 'admin_id' => 7, 'participant_id' => 8],
        ],
        'updatePhoneCall' => [
            '_' => 'updatePhoneCall',
            'phone_call' => ['_' => 'phoneCall', 'id' => 9021, 'access_hash' => 666777888999000111,
                'date' => 1700000000, 'admin_id' => 7, 'participant_id' => 8,
                'p2p_allowed' => true, 'video_available' => false],
        ],
        'updateQuickReplies' => [
            '_' => 'updateQuickReplies',
            'quick_replies' => [['_' => 'quickReply', 'shortcut_id' => 0, 'shortcut' => 'qr-0',
                'top_message' => 101, 'count' => 1]],
        ],
        'updateNewQuickReply' => [
            '_' => 'updateNewQuickReply',
            'quick_reply' => ['_' => 'quickReply', 'shortcut_id' => 1, 'shortcut' => 'qr-1',
                'top_message' => 101, 'count' => 0],
        ],
        'updateStory' => [
            '_' => 'updateStory', 'peer' => ['_' => 'peerChat', 'chat_id' => 7],
            'story' => ['_' => 'storyItem', 'id' => 9022, 'date' => 1700000000, 'expire_date' => 1800000000],
        ],
        'updateTheme' => [
            '_' => 'updateTheme',
            'theme' => ['_' => 'theme', 'id' => 9023, 'access_hash' => 777888999000111333,
                'slug' => 'a-theme', 'title' => 'A Theme', 'document' => ['_' => 'documentEmpty', 'id' => 0],
                'settings' => []],
        ],
        'updatePeerWallpaper' => [
            '_' => 'updatePeerWallpaper', 'wallpaper_overridden' => false,
            'peer' => ['_' => 'peerChat', 'chat_id' => 7],
            'wallpaper' => ['_' => 'wallPaper', 'id' => 9024, 'access_hash' => 888999000111222333,
                'slug' => 'a-wallpaper', 'document' => ['_' => 'documentEmpty', 'id' => 0], 'settings' => []],
        ],
        'updateDialogFilter' => [
            '_' => 'updateDialogFilter', 'id' => 2,
            'filter' => ['_' => 'dialogFilter', 'id' => 2, 'title' => 'Clients', 'emoticon' => '',
                'color' => 0, 'pinned_peers' => [], 'include_peers' => [], 'exclude_peers' => []],
        ],
        'users.getFullUser' => [
            '_' => 'users.userFull', 'id' => 7, 'full_user' => ['_' => 'userFull', 'blocked' => false,
                'phone_calls_available' => false, 'phone_calls_private' => false, 'can_pin_message' => false,
                'has_scheduled' => false, 'video_calls_available' => false, 'voice_messages_forbidden' => false,
                'translations_disabled' => false, 'stories_pinned_available' => false, 'blocked_my_stories_from' => false,
                'about' => 'a bot', 'common_chats_count' => 0, 'folder_id' => 0, 'ttl_period' => 0,
                'theme_emoticon' => '', 'bot_info' => ['_' => 'botInfo', 'user_id' => 7, 'description' => 'a bot',
                    'commands' => []], 'personal_photo' => ['_' => 'photoEmpty', 'id' => 0]],
        ],
        'messages.getFullChat' => [
            '_' => 'messages.chatFull', 'full_chat' => ['_' => 'chatFull', 'id' => 9,
                'about' => 'a group', 'participants' => ['_' => 'chatParticipants', 'participants' => [], 'version' => 1],
                'can_set_username' => true, 'has_scheduled' => false, 'translations_disabled' => false,
                'participants_hidden' => false, 'available_min_id' => 0, 'available_max_id' => 0,
                'bot_info' => ['_' => 'botInfo', 'user_id' => 8, 'description' => 'a group bot', 'commands' => []]],
        ],
        'account.getBusinessChatLinks' => [
            '_' => 'account.businessChatLinks', 'count' => 1, 'links' => [
                ['_' => 'businessChatLink', 'link' => 'https://t.me/+synthetic', 'chat' => ['_' => 'chat', 'id' => 9, 'title' => 'A Group', 'type' => 'group', 'participants_count' => 2, 'date' => 1700000000, 'version' => 1, 'photo' => ['_' => 'chatPhotoEmpty']], 'message' => 'join', 'views' => 0, 'entities' => []]],
        ],
        'messages.getSavedDialogs' => [
            '_' => 'messages.savedDialogs', 'dialogs' => [
                ['_' => 'savedDialog', 'peer' => ['_' => 'peerChat', 'chat_id' => 9], 'top_message' => 104]],
            'messages' => [], 'chats' => [], 'users' => [],
        ],
        'payments.getSavedStarGifts' => [
            '_' => 'payments.savedStarGifts', 'count' => 1, 'chat_notifications_enabled' => false,
            'gifts' => [['_' => 'savedStarGift', 'date' => 1700000000,
                'gift' => ['_' => 'starGift', 'id' => 9030, 'sticker' => ['_' => 'documentEmpty', 'id' => 0],
                    'stars' => 25, 'background' => ['_' => 'starsBackground', 'type' => 'solid',
                        'color' => 0, 'custom_emoji_id' => 0], 'publisher_chat' => ['_' => 'chatEmpty', 'id' => 0],
                    'upgrade_stars' => 0]]],
            'next_offset' => '', 'chats' => [], 'users' => [],
        ],
        'payments.getStarsStatus' => [
            '_' => 'payments.starsStatus', 'balance' => ['_' => 'starsAmount', 'amount' => 100, 'nstars' => 0],
            'subscriptions' => [['_' => 'starsSubscription', 'id' => 'sub-1', 'until_date' => 1800000000,
                'peer' => ['_' => 'starsSubscriptionPeerUser', 'user_id' => 7, 'monthly_price' => 250],
                'currency' => 'stars']],
            'subscriptions_next_offset' => '',
            'history' => [
                ['_' => 'starsTransaction', 'id' => 'st-1', 'date' => 1700000000, 'amount' => ['_' => 'starsAmount', 'amount' => 5, 'nstars' => 0]],
                ['_' => 'starsTransactionRefund', 'id' => 'st-2', 'date' => 1700000000, 'amount' => ['_' => 'starsAmount', 'amount' => 2, 'nstars' => 0]],
            ],
            'next_offset' => '', 'chats' => [], 'users' => [],
        ],
        // ── decoded RPC results: MirrorResultIngester ──
        'messages.getDialogs' => [
            '_' => 'messages.dialogs',
            'dialogs' => [
                ['_' => 'dialog', 'pinned' => true, 'folder' => ['_' => 'folder', 'id' => 2,
                    'title' => 'Clients', 'photo' => ['_' => 'chatPhotoEmpty'],
                    'autofill_new_broadcasts' => false, 'autofill_public_groups' => false,
                    'autofill_new_correspondents' => false],
                    'peer' => ['_' => 'peerUser', 'user_id' => 7], 'top_message' => 104,
                    'unread_muted_peers_count' => 0, 'unread_unmuted_peers_count' => 0,
                    'unread_muted_messages_count' => 0, 'unread_unmuted_messages_count' => 0],
                ['_' => 'dialogFolder', 'pinned' => false, 'folder' => ['_' => 'folder', 'id' => 3,
                    'title' => 'Bots', 'photo' => ['_' => 'chatPhotoEmpty'],
                    'autofill_new_broadcasts' => false, 'autofill_public_groups' => false,
                    'autofill_new_correspondents' => false],
                    'peer' => ['_' => 'peerChat', 'chat_id' => 9], 'top_message' => 0,
                    'unread_muted_peers_count' => 0, 'unread_unmuted_peers_count' => 0,
                    'unread_muted_messages_count' => 0, 'unread_unmuted_messages_count' => 0],
            ],
            'messages' => [], 'chats' => [], 'users' => [],
        ],
        'channels.getFullChannel' => [
            '_' => 'channelFull', 'id' => 555000111, 'access_hash' => 999000111222333444,
            'title' => 'A Channel', 'bot_info' => ['_' => 'botInfo', 'user_id' => 7, 'description' => 'a bot'],
            'stickerset' => ['_' => 'stickerSet', 'id' => 9009, 'access_hash' => 444555666777888999,
                'title' => 'Pack', 'short_name' => 'pack', 'count' => 12, 'hash' => 0],
        ],
        'messages.getHistory' => [
            '_' => 'messages.messages', 'messages' => [],
            'topics' => [['_' => 'forumTopic', 'id' => 10, 'date' => 1700000000,
                'peer' => ['_' => 'peerChannel', 'channel_id' => 555000111], 'top_message' => 101]],
            'chats' => [], 'users' => [],
        ],
        // `tf_chats` and `tf_users` ride the SAME `messages.Dialogs` envelope as
        // `tf_dialogs`, on its `chats`/`users` vectors. An EMPTY vector walks to
        // nothing — which is itself the lesson of this gate, so these carry real
        // rows rather than `[]`.
        //
        // The `chats` vector is `Vector<Chat>` and `Channel` IS a `Chat`
        // (`channel#eab7ac3 ... :Chat`), so a real dialogs page carries channels
        // ALONGSIDE chats: a peer the account holds a CHANNEL dialog with is not
        // a group, and it arrives in the same vector. This envelope carried only
        // the group, which is why `tf_channels` read as unreachable while
        // `tf_chats` — reached by the very same declared edge — was covered.
        //
        // That split is what the walker resolves by the item's OWN constructor:
        // `MirrorNestedSourceWalker::tableFor()`
        // (src/Schema/Mirror/MirrorNestedSourceWalker.php:167-176) routes a
        // collected item by its `_` through `tableForCtor()`, so the `channel`
        // below lands in `tf_channels` and the `chat` above lands in
        // `tf_chats`, even though ONE edge (`tf_chats` declaring
        // `messages.dialogs.chats`) carries both. That method's docblock
        // records the measured defect it replaced: before it existed, the
        // channel emitted "constructor 'channel' is not in catalog ctors" and
        // the row was silently DROPPED. The `channel` below is the regression
        // test for that, and it is a REAL wire shape — not a fixture invented
        // to make a table look covered.
        'messages.getDialogs+chats+users' => [
            '_' => 'messages.dialogs',
            'dialogs' => [
                ['_' => 'dialog', 'pinned' => false,
                    'peer' => ['_' => 'peerChat', 'chat_id' => 9], 'top_message' => 0,
                    'unread_muted_peers_count' => 0, 'unread_unmuted_peers_count' => 0,
                    'unread_muted_messages_count' => 0, 'unread_unmuted_messages_count' => 0],
                ['_' => 'dialog', 'pinned' => true,
                    'peer' => ['_' => 'peerChannel', 'channel_id' => 555000111], 'top_message' => 0,
                    'unread_muted_peers_count' => 0, 'unread_unmuted_peers_count' => 0,
                    'unread_muted_messages_count' => 0, 'unread_unmuted_messages_count' => 0],
            ],
            'messages' => [],
            'chats' => [
                ['_' => 'chat', 'id' => 9, 'title' => 'A Group', 'type' => 'group',
                    'participants_count' => 2, 'date' => 1700000000, 'version' => 1,
                    'photo' => ['_' => 'chatPhotoEmpty']],
                ['_' => 'channel', 'id' => 555000111, 'access_hash' => 987000111222333444,
                    'title' => 'A Channel', 'date' => 1700000000, 'participants_count' => 3,
                    'version' => 1, 'photo' => ['_' => 'chatPhotoEmpty']],
            ],
            'users' => [['_' => 'user', 'id' => 7, 'access_hash' => 123444555666777888,
                'first_name' => 'Ada', 'last_name' => 'L', 'username' => 'ada', 'bot' => true, 'contact' => false]],
        ],
        'messages.getDialogsSlice' => [
            '_' => 'messages.dialogsSlice', 'count' => 1,
            'dialogs' => [
                ['_' => 'dialog', 'pinned' => false,
                    'peer' => ['_' => 'peerUser', 'user_id' => 7], 'top_message' => 0,
                    'unread_muted_peers_count' => 0, 'unread_unmuted_peers_count' => 0,
                    'unread_muted_messages_count' => 0, 'unread_unmuted_messages_count' => 0],
            ],
            'messages' => [], 'chats' => [], 'users' => [],
        ],
        'messages.getAttachMenuBots' => [
            '_' => 'attachMenuBots', 'hash' => 42,
            'bots' => [['_' => 'attachMenuBot', 'bot_id' => 7, 'short_name' => 'menu',
                'peer_types' => [],
                'icons' => [['_' => 'attachMenuBotIcon', 'name' => 'icon',
                    'icon' => ['_' => 'documentEmpty', 'id' => 0]]]],
            ],
            'users' => [],
        ],
        'messages.getBotApp' => [
            '_' => 'messages.botApp', 'app' => ['_' => 'botApp', 'id' => 9010,
                'access_hash' => 222000333444555666, 'short_name' => 'app', 'title' => 'An App',
                'description' => 'does things', 'hash' => 0],
        ],
        'messages.getBotResults' => [
            '_' => 'messages.botResults', 'query' => '', 'cache_time' => 0,
            'results' => [['_' => 'botInlineResult', 'id' => 'result-1', 'type' => 'article',
                'title' => 'A Result', 'input' => ['_' => 'botInputMessageText', 'text' => 'hi', 'offset' => 0]]],
        ],
        'messages.getSavedReactionTags' => [
            '_' => 'messages.savedReactionTags',
            // Byte-shaped: `savedReactionTag#cb6ff828 flags:# reaction:Reaction
            // title:flags.0?string count:int` (api_full.tl:1814) carries NO `id`.
            // A fabricated `id` here would contradict the excuse this table is
            // excused for in UNREACHABLE below, and would let a future edge on
            // `messages.savedReactionTags.tags` land a row for the WRONG reason.
            'tags' => [['_' => 'savedReactionTag', 'title' => 'Thumbs',
                'reaction' => ['_' => 'reactionEmoji', 'emoticon' => "\xF0\x9F\x91\x8D"], 'count' => 3]],
        ],
    ];

    /**
     * Tables that cannot be reached, each with the MECHANISM that stops it.
     *
     * Every value is a non-empty reason naming a concrete property of the TL
     * scheme, the catalog or the shipped DDL. An author adding a table to the
     * catalog must either make it reachable or move it here WITH a reason —
     * and the reason is what a later reader audits, so vagueness is the defect
     * this gate exists to catch.
     *
     * @var array<string, string>
     */
    private const UNREACHABLE = [
        'tf_reactions' => 'UNREACHABLE ON THE MESSAGE PATH — its one VALID edge reaches nothing, and the fixing edge is '
            .'validator-REJECTED. The edge `reactionCount.reaction` validates (measured: OK), but it sits '
            .'TWO wrapper levels down: `ReactionCount` is carried only by `messageReactions.results`, '
            .'`updateBotMessageReactions.reactions` and `storyViews.reactions`, while `MessageReactions` is '
            .'carried by `message.reactions`. `MirrorNestedSourceWalker::declaredParentTypesFor()` descends '
            .'one level per `walk()` call, gated on a declared parent TYPE (src/Schema/Mirror/'
            .'MirrorNestedSourceWalker.php:181-198), and `MessageReactions` is not a declared parent type, '
            .'so the walk never stands on `messageReactions` and therefore never reaches `reactionCount`. '
            .'MEASURED 2026-10-02: `collect()` on a `message` carrying `reactions.results` => 0 facts; on '
            .'`updateMessageReactions` => 0 facts; on `updateBotMessageReactions` => 1 fact. The obvious '
            .'second edge is REJECTED by the catalog validator, correctly: '
            .'"tf_reactions: source \'messageReactions.results\' is declared to carry Reaction, but '
            .'messageReactions declares it as ReactionCount". Closing this needs either a declared '
            .'`MessageReactions` parent (a catalog entry for a type that has no table) or a walk that '
            .'descends two levels — both are design changes beyond adding edges.',

        'tf_todo_lists' => 'UNREACHABLE BY KEY — the edge VALIDATES and WALKS, and no row can land anyway. '
            .'The edge `messageMediaToDo.todo` is accepted by the real MirrorCatalog::load() (measured: OK) '
            .'and the walker returns the fact, but the shipped DDL '
            .'(src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php:160-167) keys the table '
            .'`primary([\'account_id\', \'todo_list_id\'])`, and NO payload carries `todo_list_id`: '
            .'`TL:2067 todoList#49b92a26 flags:# others_can_append:flags.0?true '
            .'others_can_complete:flags.0?true title:TextWithEntities list:Vector<TodoItem>` carries no id '
            .'at all, and its only reachable parent `TL:141 messageMediaToDo#8a53b014 flags:# '
            .'todo:TodoList completions:flags.0?Vector<TodoCompletion>` carries none either, so a '
            .'`context: ["id"]` overlay — the arm that makes `tf_message_actions` keyable — has nothing to '
            .'read. MEASURED: with the edge installed the walker yields the fact and '
            .'MirrorFactDecomposer::decompose() still cannot fill `todo_list_id`. Declaring the edge would '
            .'convert an honest empty table into a promise no row keeps. The fix is a MIGRATION change '
            .'(Task 2.2 owns migrations): the key must become something the wire carries — '
            .'`(message identity, position)`. Its SIBLING `tf_todo_items` is the control that proves the '
            .'walk is fine: same payload, one level deeper, and it places, because '
            .'`TL:2065 todoItem#cba9a52f id:int title:TextWithEntities` DOES carry an id.',

        'tf_saved_reaction_tags' => 'UNREACHABLE BY KEY — same mechanism as `tf_todo_lists`, same '
            .'deliberate omission. The edge `messages.savedReactionTags.tags` is ACCEPTED by the real '
            .'validator (measured: OK) and walks, but the shipped DDL '
            .'(src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php:106-112) keys it '
            .'`primary([\'account_id\', \'saved_reaction_tag_id\'])`, and nothing carries '
            .'`saved_reaction_tag_id`: `TL:1814 savedReactionTag#cb6ff828 flags:# reaction:Reaction '
            .'title:flags.0?string count:int` has no id, and its only carrier '
            .'`TL:1817 messages.savedReactionTags#3259950a tags:Vector<SavedReactionTag> hash:long` has '
            .'none either. A tag\'s real identity is its `reaction`, which would make the key '
            .'`(account_id, reaction)` — again a MIGRATION change (Task 2.2). This entry is additionally '
            .'pinned as undeclared by '
            .'tests/Ingest/MirrorDeclaredSourcesPlaceRowTest::test_a_table_whose_key_no_payload_can_fill_is_left_undeclared '
            .'and as unplaceable by '
            .'tests/Schema/MirrorResolverKeyMatchesShippedDdlTest::UNPLACEABLE_KEY_TABLES.',

        'tf_admin_log_events' => 'UNREACHABLE **BY POLICY, deliberately** — and the method that would deliver it is refused at '
            .'runtime by `MirrorResultPolicy::DEFAULT_METHODS`, on purpose. The edge '
            .'`channels.adminLogResults.events` VALIDATES and walks (measured: 1 fact), so this is NOT a '
            .'layer-227 gap. It is refused because the table\'s SCOPE is absent from the TL: '
            .'`channelAdminLogEvent#1fad68cd` is `id:long date:int user_id:long '
            .'action:ChannelAdminLogEventAction` — no chat id, no channel id, no peer — and its container '
            .'`channels.adminLogResults#ed8af74d` names none either; the channel lives on the METHOD\'s '
            .'`channel:InputChannel` parameter. The shipped `primary([\'account_id\', \'id\'])` would '
            .'therefore collide across every channel this account administers. See '
            .'src/Teleframe/Ingest/MirrorResultPolicy.php:60-116 (keep it OUT of DEFAULT_METHODS) and '
            .'tests/Schema/MirrorScopedKeyCorrectnessTest::test_the_admin_log_method_is_not_admitted. '
            .'The fix is a resolver threading the RPC chat parameter into the key, not an edge.',
    ];

    public function test_every_catalogued_table_has_an_edge_or_a_written_excuse(): void
    {
        $catalog = $this->shippedCatalog();
        $walker = new MirrorNestedSourceWalker($catalog);

        // What the walker ACTUALLY yields, per envelope — measured, not assumed.
        $walked = [];
        foreach (self::REACHABLE as $label => $payload) {
            foreach ($walker->collect($payload) as $fact) {
                $walked[$fact['table']][] = $label;
            }
        }

        // A table is covered when the walk REACHES it, or when the catalog gives
        // it a ctor the top-level route can dispatch. The second arm is not a
        // loophole: it is how a top-level entity table is meant to arrive, and
        // `tf_messages` is the shipped example.
        $uncovered = [];
        foreach ($catalog->tableNames() as $table) {
            if (isset($walked[$table])) {
                continue;
            }
            if ($this->routableTopLevel($catalog, $table)) {
                continue;
            }
            if (isset(self::UNREACHABLE[$table])) {
                continue;
            }
            $uncovered[] = $table;
        }

        self::assertSame(
            [],
            $uncovered,
            "catalogued tables with neither a reachable edge nor a written excuse.\n"
            ."Each is a Telegram fact the mirror can never recover, silently.\n"
            .'Reachable tables the walk actually found: '.count($walked)."\n"
            .'Excused tables: '.count(self::UNREACHABLE)."\n"
            .'Fix: add a validated `sources` edge (with its descent and key), or record WHY here.',
        );
    }

    /**
     * The gate must be able to FAIL. If every table were excused, or if the
     * excuses were not really consulted, the test above would pass on a catalog
     * with no edges at all — so this pins both directions:
     * every excuse is genuinely needed, and every excuse is a real reason.
     */
    public function test_an_excuse_is_only_permitted_for_a_table_that_really_is_unreachable(): void
    {
        $catalog = $this->shippedCatalog();
        $walker = new MirrorNestedSourceWalker($catalog);

        $walked = [];
        foreach (self::REACHABLE as $label => $payload) {
            foreach ($walker->collect($payload) as $fact) {
                $walked[$fact['table']][] = $label;
            }
        }

        foreach (self::UNREACHABLE as $table => $reason) {
            self::assertTrue(
                $catalog->has($table),
                "UNREACHABLE names '{$table}', which is not in the catalog — remove the stale excuse",
            );
            self::assertNotSame(
                '',
                trim($reason),
                "UNREACHABLE['{$table}'] must carry a reason; \"we could not think of one\" is not a reason",
            );
            self::assertFalse(
                isset($walked[$table]),
                "UNREACHABLE['{$table}'] is now REACHED by the walker (via: "
                .implode(', ', $walked[$table] ?? []).') — delete the excuse so the table is honestly covered',
            );
            self::assertFalse(
                $this->routableTopLevel($catalog, $table),
                "UNREACHABLE['{$table}'] is routable by its own top-level ctor — delete the excuse",
            );
        }

        // And the walk must not be vacuous: an empty REACHABLE map would let
        // every excuse through while proving the envelopes were never walked.
        self::assertNotSame(
            [],
            $walked,
            'no envelope in REACHABLE yielded a single fact — the probe itself is broken, so the '
            .'gate above would pass for the wrong reason',
        );
    }

    /**
     * The top-level arm added on 2026-10-11 must be able to FAIL.
     *
     * The arm was widened because it was INCOMPLETE: it read only the five
     * `UPDATE_FACT_FIELD` wrappers and so reported three routable tables
     * (`tf_updates`, `tf_channel_updates`, `tf_update_differences`) as
     * unreachable. Widening a guard is exactly where a mistake buys a green
     * build, so this pins it from the failing side in three ways:
     *
     *  1. The derivation must be NON-EMPTY and must actually contain the
     *     constructors that carry the fix — an arm derived from a broken
     *     classifier would otherwise return `[]` and quietly admit nothing.
     *  2. A table whose constructors are ALL inside `UPDATE_FACT_FIELD` must
     *     still read UNROUTABLE. That is the exclusion the unwrap map buys:
     *     `updateNewMessage` reaches `tableForCtor()` carrying `message`, so
     *     crediting a table for it would credit a row the write path never
     *     routes. A synthetic catalog is used so the property is proven
     *     directly rather than inferred from whichever shipped table happens
     *     to have that shape today.
     *  3. The four UNREACHABLE tables must stay unroutable under the WIDENED
     *     arm. They all declare real constructors, so before the early return
     *     and the `update*` restriction they WOULD have been admitted — which
     *     would have converted four true excuses into false reachability.
     */
    public function test_the_top_level_arm_is_derived_and_still_refuses_a_fully_unwrapped_table(): void
    {
        $catalog = $this->shippedCatalog();

        // 1. The derivation is non-empty AND carries the constructors the fix
        //    depends on — named from the seam's own routing, not transcribed
        //    as a table list.
        $surface = $this->updateSurfaceCtors($catalog);
        self::assertNotSame(
            [],
            $surface,
            'the derived update surface is empty, so the widened top-level arm admits nothing',
        );
        foreach (['updateUserStatus', 'updates.channelDifference', 'updates.difference'] as $ctor) {
            self::assertContains(
                $ctor,
                $surface,
                "the derived update surface no longer contains '{$ctor}', so the top-level arm can no "
                .'longer route the table that constructor belongs to — the incompleteness measured on '
                .'2026-10-11 would return',
            );
        }

        // The unwrap map's own keys must NOT be in the surface: they are
        // replaced by their inner entity before routing reaches the catalog.
        foreach (array_keys((new \ReflectionClass(MirrorIngesterFactory::class))->getConstant('UPDATE_FACT_FIELD')) as $unwrapped) {
            self::assertNotContains(
                $unwrapped,
                $surface,
                "'{$unwrapped}' is unwrapped by UPDATE_FACT_FIELD, so tableForCtor() never sees it under "
                .'that name — crediting a table for it would credit a row the write path never routes',
            );
        }

        // 2. A table whose every ctor is unwrapped away reads UNROUTABLE.
        //
        //    Built through `MirrorCatalog::load()` against the REAL TL scheme,
        //    not `fromEntries()`: `updateDispatchableCtors()` resolves each
        //    unwrap entry through `paramsOfCtor()`, which returns nothing for a
        //    catalog built without a scheme — so `fromEntries()` would make
        //    this control fail on a missing `message` param rather than on the
        //    property under test. The fixture is written to a temp file and
        //    removed in a `finally`, and it names NO table that exists in the
        //    shipped catalog.
        $fixture = tempnam(sys_get_temp_dir(), 'tf_ctl_');
        self::assertIsString($fixture, 'could not create a temp catalog fixture');

        try {
            file_put_contents($fixture, json_encode([
                'tf_ctl_all_unwrapped' => [
                    'tl' => 'ControlAllUnwrapped',
                    'ctors' => ['updateNewMessage', 'updateEditMessage', 'updateGroupCall'],
                    'base' => [['id', 'long']],
                    'bools' => [],
                    'children' => [],
                ],
                'tf_ctl_surface_ctor' => [
                    'tl' => 'ControlSurface',
                    'ctors' => ['updateUserStatus'],
                    'base' => [['id', 'long']],
                    'bools' => [],
                    'children' => [],
                ],
                'tf_ctl_non_update_ctor' => [
                    'tl' => 'ControlNonUpdate',
                    'ctors' => ['reactionEmoji'],
                    'base' => [['id', 'long']],
                    'bools' => [],
                    'children' => [],
                ],
            ], JSON_THROW_ON_ERROR));

            $synthetic = MirrorCatalog::load($fixture, TlParser::parseFile(self::TL));
        } finally {
            @unlink($fixture);
        }

        self::assertFalse(
            $this->routableTopLevel($synthetic, 'tf_ctl_all_unwrapped'),
            'a table whose constructors are ALL unwrapped away by UPDATE_FACT_FIELD must still read '
            .'unroutable — unwrapUpdate() hands tableForCtor() the inner entity, never these names',
        );
        self::assertTrue(
            $this->routableTopLevel($synthetic, 'tf_ctl_surface_ctor'),
            'a table owning a real update-surface constructor must read routable, or the widened arm '
            .'admits nothing and the defect returns',
        );
        self::assertFalse(
            $this->routableTopLevel($synthetic, 'tf_ctl_non_update_ctor'),
            'a table whose only constructor is not on the update surface must read UNROUTABLE — a bare '
            .'reaction payload never arrives as an update, which is what UNREACHABLE[\'tf_reactions\'] records',
        );

        // 3. The four written excuses survive the widened arm. All four declare
        //    real constructors, so without the `update*` restriction each would
        //    have been admitted and its TRUE excuse silently destroyed.
        foreach (array_keys(self::UNREACHABLE) as $table) {
            self::assertFalse(
                $this->routableTopLevel($catalog, $table),
                "UNREACHABLE['{$table}'] became routable under the widened top-level arm — the arm is now "
                .'too permissive and would excuse a real coverage gap',
            );
        }
    }

    /**
     * A declared edge that reaches NOTHING is the failure mode this whole task
     * exists to remove, and it is invisible to a "has a `sources` key" check —
     * `tf_reactions` carries one and places no row on the message path. So
     * every table the catalog declares an edge for must be one the walker
     * actually reaches from at least one realistic envelope.
     *
     * The exceptions are the tables whose excuse NAMES this exact defect.
     */
    public function test_a_declared_edge_is_reached_by_some_realistic_envelope(): void
    {
        $catalog = $this->shippedCatalog();
        $walker = new MirrorNestedSourceWalker($catalog);

        $walked = [];
        foreach (self::REACHABLE as $label => $payload) {
            foreach ($walker->collect($payload) as $fact) {
                $walked[$fact['table']][] = $label;
            }
        }

        $declaredButDead = [];
        foreach ($catalog->nestedSources() as $source) {
            if (! isset($walked[$source->tfName])) {
                $declaredButDead[$source->tfName] = $source->describe();
            }
        }

        // A declared-but-unreached edge is acceptable ONLY when UNREACHABLE
        // names that table and says the edge reaches nothing.
        foreach ($declaredButDead as $table => $edge) {
            self::assertArrayHasKey(
                $table,
                self::UNREACHABLE,
                "{$table} declares the edge {$edge}, but no envelope in REACHABLE walks it. "
                .'An edge that reaches nothing is worse than no edge: it reads as fixed while placing '
                .'no row. Either add the envelope that carries it, or record it in UNREACHABLE with '
                .'the reason it cannot be walked.',
            );
        }

        self::assertNotSame(
            [],
            $walked,
            'the probe yielded no facts at all, so "every declared edge is reached" would pass vacuously',
        );
    }

    /**
     * Whether the update seam can dispatch this table from a payload's OWN
     * top-level constructor — the other legitimate way a row arrives.
     *
     * ## Why this reads the seam's own constant, not the catalog's `ctors`
     *
     * `MirrorIngesterFactory` calls `MirrorCatalog::tableForCtor($payload['_'])`
     * and returns early when that is null (src/Laravel/MirrorIngesterFactory.php:120-121).
     * `unwrapUpdate()` (same file, 661-672) rewrites the payload ONLY when its
     * `_` is one of the five keys in `UPDATE_FACT_FIELD`; every other payload
     * passes through with its own `_` intact. So the seam dispatches on the
     * payload's top-level constructor for the WHOLE update surface, not only
     * for the five wrappers — and "this table has ctors" is still not
     * routability on its own: `tf_reactions` lists 4 `reaction*` ctors and a
     * bare `reaction` payload never arrives as an update (it rides
     * `message.reactions.results`, the nested edge the walker is for).
     *
     * ## The defect this method had, MEASURED 2026-10-11
     *
     * The previous version built its dispatchable-ctor set from
     * `UPDATE_FACT_FIELD` ALONE — `updateDispatchableCtors()` below — and so
     * admitted only `message`, `messageEmpty`, `messageService` and
     * `groupCall`. That model was INCOMPLETE, and the incompleteness was not
     * academic: it reported three genuinely-routable tables as unroutable.
     * Commit `05e1488d` added `tf_updates`, `tf_channel_updates` and
     * `tf_update_differences` to the catalog with real constructors, and this
     * gate went red on exactly those three while `tf_channels` stayed red for
     * a different reason (see below). The parent's measured proof that all
     * three route, reproduced here against the shipped catalog:
     *
     * ```
     * tableForCtor('updateUserStatus')       -> tf_updates
     * tableForCtor('updates.channelDifference')-> tf_channel_updates
     * tableForCtor('updates.difference')     -> tf_update_differences
     * ```
     *
     * None of those `_` values is a key of `UPDATE_FACT_FIELD`, so
     * `unwrapUpdate()` returns the payload untouched and the seam routes it by
     * that constructor. The four constructors the old arm DID admit are simply
     * the subset that happens to sit inside the unwrap map.
     *
     * ## THE NEW ARM, AND WHY IT IS DERIVED RATHER THAN TYPED
     *
     * The dispatchable set is now the whole UPDATE SURFACE — every constructor
     * the TL scheme declares whose name begins with `update` — taken from
     * `EndpointCoverage::classify()`, which partitions that set totally from the
     * scheme alone (MEASURED: 175 endpoints, and the classified set is exactly
     * the scheme's `update*` constructor set). Reading a real classifier rather
     * than a literal matters here: a hand-typed list of the three table names
     * is exactly the artefact this gate exists to replace, and it would go
     * stale the moment the catalog gained a fourth.
     *
     * Constructors that `UPDATE_FACT_FIELD` unwraps away are still EXCLUDED.
     * That is not bookkeeping: `updateNewMessage` reaches `tableForCtor()`
     * carrying the ctor of its INNER entity (`message`), so crediting
     * `tf_updates` for it would credit a row the write path never routes.
     * MEASURED: excluding the five leaves each of the three tables with
     * constructors that genuinely reach the seam —
     * `tf_updates` keeps `updateUserStatus`, `updateDeleteMessages`,
     * `updateChannelParticipant`, … (7 of its 11), and both difference tables
     * keep all of theirs.
     *
     * The early return below is UNCHANGED and still deliberate: a table the
     * catalog gives a nested `sources` edge is reached by the walker, and the
     * walker is the arm that must prove it.
     *
     * ## AND THE ONE TABLE THAT WAS NOT THIS ARM AT ALL
     *
     * `tf_channels` is NOT top-level dispatchable — `channel`/`channelForbidden`
     * are not `update*` constructors and never reach the bus. It is reachable
     * by arm 1 instead: `MirrorNestedSourceWalker::tableFor()` (line 167-176)
     * routes a collected item by the item's OWN constructor, so a `channel`
     * inside a `messages.dialogs` `chats` VECTOR resolves to `tf_channels`
     * even though the edge belongs to `tf_chats`. MEASURED: the walker over a
     * dialogs page carrying one `channel` returns `tf_channels`. The
     * `REACHABLE` envelope below carries exactly that, so this table is covered
     * by the walk rather than by an excuse or by this arm.
     */
    private function routableTopLevel(MirrorCatalog $catalog, string $table): bool
    {
        // A table with a nested edge is reached through the walker; its own
        // ctors are never handed to tableForCtor() on this path.
        foreach ($catalog->nestedSources() as $source) {
            if ($source->tfName === $table) {
                return false;
            }
        }

        foreach (self::updateDispatchableCtors($catalog) as $ctor) {
            if ($catalog->tableForCtor($ctor)?->tfName === $table) {
                return true;
            }
        }

        // The rest of the update surface. Derived from the scheme, so it cannot
        // drift the way the old unwrap-map-only arm did.
        foreach (self::updateSurfaceCtors($catalog) as $ctor) {
            if ($catalog->tableForCtor($ctor)?->tfName === $table) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every constructor the update seam can be handed as a payload's own top
     * `_`, minus the ones `UPDATE_FACT_FIELD` unwraps away before routing.
     *
     * Derived from `EndpointCoverage::classify()` — the real classifier, which
     * partitions the scheme's whole `update*` constructor set from declared
     * shape alone — rather than from a list kept beside it.
     *
     * @return list<string>
     */
    private static function updateSurfaceCtors(MirrorCatalog $catalog): array
    {
        /** @var array<string, string> $unwrap */
        $unwrap = (new \ReflectionClass(MirrorIngesterFactory::class))
            ->getConstant('UPDATE_FACT_FIELD');

        $coverage = EndpointCoverage::classify(
            TlParser::parseFile(self::TL),
            $catalog,
        );

        $endpoints = $coverage->endpoints();

        self::assertNotSame(
            [],
            $endpoints,
            'EndpointCoverage classified no update endpoint, so the top-level arm of this gate would '
            .'admit nothing beyond the unwrap map — which is exactly the incompleteness measured on '
            .'2026-10-11, and it would report tf_updates / tf_channel_updates / tf_update_differences '
            .'as unreachable when the seam routes all three',
        );

        $ctors = [];
        foreach ($endpoints as $endpoint) {
            $ctor = (string) $endpoint['ctor'];
            if (isset($unwrap[$ctor])) {
                continue; // unwrapUpdate() hands tableForCtor() the INNER entity instead
            }
            $ctors[$ctor] = true;
        }

        self::assertNotSame(
            [],
            $ctors,
            'every classified update endpoint is inside UPDATE_FACT_FIELD, so this arm is vacuous — '
            .'no constructor would reach tableForCtor() under its own name',
        );

        return array_keys($ctors);
    }

    /**
     * Constructors the update seam can route to a catalog table, derived from
     * `MirrorIngesterFactory::UPDATE_FACT_FIELD` and the TL scheme so the gate
     * audits the real code rather than a list kept beside it.
     *
     * @return list<string>
     */
    private static function updateDispatchableCtors(MirrorCatalog $catalog): array
    {
        /** @var array<string, string> $map */
        $map = (new \ReflectionClass(MirrorIngesterFactory::class))
            ->getConstant('UPDATE_FACT_FIELD');

        self::assertNotSame(
            [],
            $map,
            'MirrorIngesterFactory::UPDATE_FACT_FIELD disappeared — the unwrap seam this gate relies on '
            .'is gone, so top-level routability can no longer be derived and every such table needs a '
            .'nested edge or an excuse',
        );

        $types = TlParser::parseFile(self::TL)->types();
        $ctors = [];
        foreach ($map as $updateCtor => $field) {
            $params = $catalog->paramsOfCtor($updateCtor);
            self::assertArrayHasKey(
                $field,
                $params,
                "MirrorIngesterFactory::UPDATE_FACT_FIELD names '{$updateCtor} => {$field}', which "
                .'layer 227 does not carry — the unwrap would never find a payload to route',
            );

            $baseType = $params[$field]->baseType();
            $type = $types[$baseType] ?? null;
            self::assertNotNull(
                $type,
                "the unwrapped field '{$updateCtor}.{$field}' has type {$baseType}, "
                .'which is not in the TL scheme',
            );

            foreach ($type->constructors() as $ctor) {
                $ctors[$ctor->name] = true;
            }
        }

        self::assertNotSame(
            [],
            $ctors,
            'no constructor is dispatchable from any bus update, so the top-level arm of this gate '
            .'would be vacuous',
        );

        return array_keys($ctors);
    }

    private function shippedCatalog(): MirrorCatalog
    {
        return MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL));
    }

    /**
     * The declared `sources` of one table, for the value-asserting edge tests.
     *
     * @return list<MirrorNestedSource>
     */
    private function sourcesFor(MirrorCatalog $catalog, string $table): array
    {
        $out = [];
        foreach ($catalog->nestedSources() as $source) {
            if ($source->tfName === $table) {
                $out[] = $source;
            }
        }

        return $out;
    }

    public function test_a_table_that_declares_an_edge_names_a_real_wire_field(): void
    {
        $catalog = $this->shippedCatalog();

        foreach ($catalog->tableNames() as $table) {
            $entry = $catalog->table($table);
            if ($entry->sources === null) {
                continue;
            }

            foreach ($this->sourcesFor($catalog, $table) as $source) {
                self::assertNotSame(
                    '',
                    $source->field,
                    "{$table}: the declared edge must name the wire field it reads",
                );
                self::assertNotSame(
                    '',
                    $source->parentTlType,
                    "{$table}: the declared edge must resolve a parent TYPE the walker can descend into",
                );
            }
        }
    }
}
