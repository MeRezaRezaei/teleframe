<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Group T/U/W of the 175-constructor `update*` endpoint universe: every
 * constructor whose first letter after `update` is T, U or W.
 *
 * MEASURED COUNT: 12 endpoints — T=2, U=6, W=4.
 *
 * This is ONE MORE than the plan's expected 11 (T=2, U=5, W=4). The
 * discrepancy is on the U side and the plan's correction is WRONG for
 * v227. Measured with:
 *
 *   php -r 'require "vendor/autoload.php"; $s=MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(__DIR__."/schema/sources/TL_telegram_v227.tl"); $n=[]; foreach($s->types() as $t){ foreach($t->constructors() as $cn=>$c){ $cn=(string)$cn; if(str_starts_with($cn,"update")) $n[$cn]=true; } } ksort($n); foreach(array_keys($n) as $k){ $l=strtoupper(substr($k,6,1)); if(in_array($l,["T","U","W"],true)) echo $k,"\n"; }'
 *
 * D-016 predicted that the raw 6 U lines contain one RPC METHOD and so U
 * contributes only 5 CONSTRUCTORS. Measured: `$s->methods()` contains NO
 * `update*` name at all (zero hits, no `METHOD LEAK`), and all six of
 * updateUser, updateUserEmojiStatus, updateUserName, updateUserPhone,
 * updateUserStatus, updateUserTyping ARE real constructors in the
 * `Update` union — dumped from `$s->types()["Update"]->constructors()`,
 * which holds 160 entries. The plan's `updates.getState` theory does not
 * hold at the v227 layer, so `updateUserStatus` is INCLUDED here rather
 * than silently dropped. All 12 are constructors, 0 are methods, so the
 * count reported upward is 12 and the D-016 arithmetic needs revisiting.
 *
 * API CORRECTION for the command above: `TlScheme` exposes constructors
 * under `types()[<type>]->constructors()` (an array keyed by BARE ctor
 * name), NOT `TlScheme::constructors()` — that method does not exist.
 * A bare `grep` would ALSO have been wrong here: it counts methods too,
 * so it is not what produced the 11.
 *
 * Shape convention: follows `tests/Ingest/Concerns/HasNestedUpdateFixtures.php`
 * exactly. `_` is set to the BARE constructor name on EVERY constructor
 * node (no `#hex` ids anywhere), set-flag booleans arrive as EXPLICIT
 * keys, vectors are PHP lists, and peer references are objects. Every
 * non-flag field each constructor declares is covered; every value is
 * SYNTHETIC (obviously-fake `access_hash` integers, no session strings,
 * no credentials, `.env` never read). Ids are kept distinct per endpoint
 * so nothing collides in a PK.
 */
trait GroupTUW
{
    /** @return array<string, array<string, mixed>> ctor name => a decoded payload */
    public static function payloads(): array
    {
        return [
            // ---- T (2) ----

            // theme = Theme → theme#c3a12462
            'updateTheme' => [
                '_' => 'updateTheme',
                'theme' => [
                    '_' => 'theme',
                    // creator | default | installs_count
                    'flags' => (1 << 0) | (1 << 1) | (1 << 4),
                    'creator' => true,
                    'default' => true,
                    'id' => 8446318901,
                    'access_hash' => 1112223334445556677,
                    'slug' => 'synthetic-theme',
                    'title' => 'Synthetic Theme',
                    'installs_count' => 42,
                ],
            ],

            // peer = Peer · msg_id:int · transcription_id:long · text:string
            // pending = flags.0?true
            'updateTranscribedAudio' => [
                '_' => 'updateTranscribedAudio',
                // pending
                'flags' => (1 << 0),
                'pending' => true,
                'peer' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
                'msg_id' => 4242,
                'transcription_id' => 998877665544332211,
                'text' => 'synthetic transcription',
            ],

            // ---- U (6) ----

            'updateUser' => [
                '_' => 'updateUser',
                'user_id' => 501558149,
            ],

            // emoji_status = EmojiStatus → emojiStatusEmpty (paramless)
            'updateUserEmojiStatus' => [
                '_' => 'updateUserEmojiStatus',
                'user_id' => 501558150,
                'emoji_status' => ['_' => 'emojiStatusEmpty'],
            ],

            // usernames = Vector<Username> → username#b4073647
            'updateUserName' => [
                '_' => 'updateUserName',
                'user_id' => 501558151,
                'first_name' => 'Syn',
                'last_name' => 'Thetic',
                'usernames' => [
                    [
                        '_' => 'username',
                        // editable | active
                        'flags' => (1 << 0) | (1 << 1),
                        'editable' => true,
                        'active' => true,
                        'username' => 'synthetic_user',
                    ],
                ],
            ],

            'updateUserPhone' => [
                '_' => 'updateUserPhone',
                'user_id' => 501558152,
                // obviously-fake number; no real subscriber line
                'phone' => '+10000000000',
            ],

            // status = UserStatus → userStatusOnline#edb93949 (expires:int)
            'updateUserStatus' => [
                '_' => 'updateUserStatus',
                'user_id' => 501558153,
                'status' => ['_' => 'userStatusOnline', 'expires' => 1724852500],
            ],

            // action = SendMessageAction → sendMessageTypingAction (paramless)
            // top_msg_id = flags.0?int
            'updateUserTyping' => [
                '_' => 'updateUserTyping',
                // top_msg_id
                'flags' => (1 << 0),
                'user_id' => 501558154,
                'top_msg_id' => 4243,
                'action' => ['_' => 'sendMessageTypingAction'],
            ],

            // ---- W (4) ----

            // exception = WebDomainException → webDomainException#a429b192
            // open_external_browser = flags.0?Bool → boolTrue#997275b5
            'updateWebBrowserException' => [
                '_' => 'updateWebBrowserException',
                // delete | open_external_browser
                'flags' => (1 << 0) | (1 << 1),
                'delete' => true,
                'open_external_browser' => ['_' => 'boolTrue'],
                'exception' => [
                    '_' => 'webDomainException',
                    'flags' => 0,
                    'domain' => 'synthetic.invalid',
                    'url' => 'https://synthetic.invalid/blocked',
                    'title' => 'Blocked Domain',
                ],
            ],

            'updateWebBrowserSettings' => [
                '_' => 'updateWebBrowserSettings',
                // open_external_browser | display_close_button
                'flags' => (1 << 0) | (1 << 1),
                'open_external_browser' => true,
                'display_close_button' => true,
            ],

            // webpage = WebPage → webPageEmpty#211a1788 (url = flags.0?string)
            'updateWebPage' => [
                '_' => 'updateWebPage',
                'webpage' => [
                    '_' => 'webPageEmpty',
                    // url
                    'flags' => (1 << 0),
                    'id' => 7000000000000000001,
                    'url' => 'https://synthetic.invalid/page',
                ],
                'pts' => 1350,
                'pts_count' => 1,
            ],

            'updateWebViewResultSent' => [
                '_' => 'updateWebViewResultSent',
                'query_id' => 8000000000000000002,
            ],
        ];
    }
}
