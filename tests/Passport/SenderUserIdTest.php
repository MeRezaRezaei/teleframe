<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\SenderUserId;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PeerChannelData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PeerChatData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PeerUserData;
use PHPUnit\Framework\TestCase;

/**
 * SenderUserIdTest — the ONE place both login chains read a sender id from.
 *
 * WHY THIS FILE EXISTS: the reader used to be a private method duplicated in
 * `PassportLoginService` and `PassportAnonymousLoginService`, and the copy read
 * `message.from.id` — the Bot API `Message.from`, a field a real MTProto
 * `Message`/`messageService` does not have (api.tl:121/122 spell it
 * `from_id:flags.8?Peer`). So a genuine `updateNewMessage` resolved to 0 and
 * every MTProto-shaped login was refused at the sender. The tests below pin
 * the two spellings and, more importantly, the REFUSALS: only a `peerUser`
 * names a user, and passport is user-only.
 */
final class SenderUserIdTest extends TestCase
{
    // ── the two documented spellings ──────────────────────────────────

    /** Bot API: `message.from.id` on a `User`. Unchanged, and still first. */
    public function test_the_bot_api_sender_is_read_from_message_from_id(): void
    {
        self::assertSame(4242, SenderUserId::resolve(['message' => ['from' => ['id' => 4242]]]));
        self::assertSame(4242, SenderUserId::resolve(['message' => ['from' => ['id' => '4242']]]));
    }

    /** MTProto: `from_id:flags.8?Peer`, the only spelling a real frame has. */
    public function test_the_mtproto_sender_is_read_from_a_peer_user_from_id(): void
    {
        self::assertSame(7, SenderUserId::resolve([
            'message' => ['from_id' => ['_' => 'peerUser', 'user_id' => 7]],
        ]));

        self::assertSame(7, SenderUserId::resolve([
            'message' => ['from_id' => ['_' => 'peerUser', 'user_id' => '7']],
        ]));

        // the generated DTO spelling of the same Peer
        self::assertSame(7, SenderUserId::resolve([
            'message' => ['from_id' => new PeerUserData(7)],
        ]));

        // the host-adapted spelling the intake reads the action from
        self::assertSame(7, SenderUserId::resolve([
            'messageService' => ['from_id' => ['_' => 'peerUser', 'user_id' => 7]],
        ]));
        self::assertSame(7, SenderUserId::resolve([
            'message_service' => ['from_id' => ['_' => 'peerUser', 'user_id' => 7]],
        ]));
    }

    /** Both spellings of a node, so a host may use either. */
    public function test_both_node_spellings_are_read_for_the_peer_itself(): void
    {
        self::assertSame(7, SenderUserId::resolve([
            'message' => ['fromId' => ['_' => 'peerUser', 'userId' => 7]],
        ]));
    }

    // ── the refusals: only a peerUser names a user ────────────────────

    public function test_a_peer_that_is_not_a_peer_user_is_refused(): void
    {
        $refused = [
            'peerChat' => ['_' => 'peerChat', 'chat_id' => 7],
            'peerChannel' => ['_' => 'peerChannel', 'channel_id' => 7],
            'peerUserSpellingButNoConstructor' => ['user_id' => 7],
            'constructorIsSomethingElse' => ['_' => 'peerSelf', 'user_id' => 7],
            'a bare string' => 'peerUser',
            'an int' => 7,
        ];

        foreach ($refused as $label => $peer) {
            self::assertSame(0, SenderUserId::resolve(['message' => ['from_id' => $peer]]), 'refused: '.$label);
        }
    }

    /** The generated non-user Peers are refused exactly as the wire ones are. */
    public function test_the_generated_non_user_peers_are_refused(): void
    {
        self::assertSame(0, SenderUserId::resolve(['message' => ['from_id' => new PeerChatData(7)]]));
        self::assertSame(0, SenderUserId::resolve(['message' => ['from_id' => new PeerChannelData(7)]]));
    }

    /**
     * The MTProto spelling of the Bot API `sender_chat` refusal: a chat is not
     * a user, and this package refuses a chat sender on BOTH sides rather than
     * binding a login to a chat id.
     */
    public function test_a_chat_sender_is_refused_exactly_as_sender_chat_is(): void
    {
        self::assertSame(0, SenderUserId::resolve(['message' => ['from_id' => ['_' => 'peerChat', 'chat_id' => 7]]]));
    }

    // ── the id itself: never zero, never negative, never junk ──────────

    public function test_a_junk_or_non_positive_id_resolves_to_zero(): void
    {
        $junk = [
            ['_' => 'peerUser', 'user_id' => 0],
            ['_' => 'peerUser', 'user_id' => -5],
            ['_' => 'peerUser', 'user_id' => '12abc'],
            ['_' => 'peerUser', 'user_id' => ''],
            ['_' => 'peerUser', 'user_id' => 1.5],
            ['_' => 'peerUser', 'user_id' => true],
            ['_' => 'peerUser'],
        ];

        foreach ($junk as $index => $peer) {
            self::assertSame(0, SenderUserId::resolve(['message' => ['from_id' => $peer]]), 'refused: case '.$index);
        }
    }

    public function test_an_update_with_no_sender_at_all_resolves_to_zero(): void
    {
        foreach ([[], ['update_id' => 1], ['message' => []], ['message' => ['from' => []]]] as $update) {
            self::assertSame(0, SenderUserId::resolve($update));
        }
    }

    /**
     * The coordinates are tried in a stated order, so the answer does not
     * depend on which one a host happened to populate last.
     */
    public function test_the_documented_order_decides_when_both_are_present(): void
    {
        $update = ['message' => [
            'from' => ['id' => 100],
            'from_id' => ['_' => 'peerUser', 'user_id' => 200],
        ]];

        self::assertSame(100, SenderUserId::resolve($update), 'the Bot API spelling is first');
    }
}
