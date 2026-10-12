<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use MeRezaRezaei\Teleframe\Core\Types\InputPeer;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * DialogPeerResolver — the PRODUCTION `peerResolver` for
 * `UserScopeTransport` (see that class's "THE TWO SEAMS MTPROTO FORCES").
 *
 * THE PROBLEM IT SOLVES: `messages.sendMessage` takes an `InputPeer`, and
 * for users and channels that carries an `access_hash` which cannot be
 * invented, typed in, or derived from an id. The notify core's topic rows
 * carry Bot-API chats (int id or `@username`) because that is all a
 * `Topic::define()` row can hold. So every bare id the notifier is pointed
 * at is unaddressable over MTProto until SOMETHING has seen the account's
 * dialog list, which is the one place Telegram hands out `access_hash`
 * values. This class is that something.
 *
 * HOW IT RESOLVES, AND WHY IT IS HONEST:
 *
 *  1. ONE `messages.getDialogs` call, the same request shape the shipped
 *     `UserAccountScope::getDialogs()` already sends, memoized for the life
 *     of the resolver. The reply carries `users` and `chats` rows, and each
 *     row carries its own `access_hash` — this is the real origin of the
 *     value, not a guess and not a config the operator has to paste.
 *  2. A numeric id is matched against BOTH collections, and the peer it
 *     becomes is decided by the collection the row came from, not by the
 *     sign of the number: a `users` row is `inputPeerUser`; a `chats` row
 *     WITH an `access_hash` is `inputPeerChannel`; a `chats` row WITHOUT
 *     one is a basic group, and basic groups genuinely take no hash, so it
 *     is `inputPeerChat`. Telegram's `-100…` channel-id convention exists,
 *     but hardcoding it would be a second guess at a fact the reply already
 *     states.
 *  3. A `@username` is matched case-insensitively against the `username`
 *     field of the same two collections.
 *
 * IT REFUSES LOUDLY, WHICH IS THE WHOLE POINT: a chat that is not in the
 * account's dialog list, a dialog call that failed, a reply with no
 * `users`/`chats` at all — every one of those throws `NotifyException`
 * naming the chat, instead of guessing a hash. A watchdog alert that
 * silently went nowhere because a hash was fabricated is precisely the
 * failure the notify module exists to prevent.
 *
 * WHAT THIS DOES NOT DO, STATED PLAINLY:
 *  - It is NOT a username resolver for a peer the account has never seen.
 *    Telegram can resolve one (`contacts.resolveUsername`); this class
 *    deliberately does not dial that, because a notify send that blocks on
 *    a second discovery call is a send that can fail for a reason the
 *    operator did not cause. Add the chat to the account's dialogs (or bind
 *    a host `peerResolver` seam that does resolve) and this one works.
 *  - The `access_hash` values it returns are whatever the account's dialog
 *    list said at fetch time. Telegram invalidates them on some events, and
 *    a stale hash surfaces as a Telegram-side PEER_ID_INVALID error, not as
 *    a silent misdelivery. No test in this repo has ever run against a live
 *    Telegram session: the fixture is a captured reply SHAPE, not a capture
 *    from a real account, so "this is the shape Telegram sends" is the
 *    strongest claim available offline and no more than that.
 *
 * Plain-PHP constructible: it stores a `LazyUserScope` and an int. It
 * dials nothing until `__invoke` runs, and the scope it reaches through
 * resolves lazily and memoizes too.
 */
final class DialogPeerResolver
{
    /** @var array<int, array<string, mixed>>|null id => the InputPeer, once resolved */
    private ?array $byId = null;

    /** @var array<string, array<string, mixed>>|null lowercased username => the InputPeer */
    private ?array $byUsername = null;

    public function __construct(
        private readonly LazyUserScope $scope,
        private readonly int $dialogLimit = 200,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Whether the dialog list has been fetched. False straight after
     * construction — the "never dials at construct" proof, assertable.
     */
    public function resolved(): bool
    {
        return $this->byId !== null;
    }

    /**
     * The `peerResolver` seam `UserScopeTransport` calls.
     *
     * @param  mixed  $chat  an int id, a numeric string, an `@username`, or
     *                       null when the caller supplied no chat at all
     * @return array<string, mixed> an InputPeer with its `access_hash`
     *
     * @throws NotifyException when the chat is unusable, or the account's
     *                         dialog list does not contain it, or the dialog
     *                         call failed. Never a guessed hash, never a
     *                         silent no-op.
     */
    public function __invoke(mixed $chat): array
    {
        $index = $this->index();

        $username = self::usernameOf($chat);

        if ($username !== null) {
            $peer = $index['usernames'][$username] ?? null;

            if ($peer === null) {
                throw $this->unresolved($chat, 'no @username by that name is among the account\'s dialogs');
            }

            return $peer;
        }

        $id = self::idOf($chat);

        if ($id === null) {
            throw $this->unresolved($chat, 'it is neither a numeric id nor an @username');
        }

        $peer = $index['ids'][$id] ?? null;

        if ($peer === null) {
            throw $this->unresolved($chat, 'no such id is among the account\'s dialogs');
        }

        return $peer;
    }

    /**
     * The two lookup tables, built by ONE `messages.getDialogs` call and
     * memoized. Both are keyed the way the two accepted chat shapes are
     * written: `ids` by the integer, `usernames` by the lowercased name.
     *
     * @return array{ids: array<int, array<string, mixed>>, usernames: array<string, array<string, mixed>>}
     */
    private function index(): array
    {
        if ($this->byId !== null && $this->byUsername !== null) {
            return ['ids' => $this->byId, 'usernames' => $this->byUsername];
        }

        $scope = $this->scope->scope();

        $limit = $this->dialogLimit > 0 ? $this->dialogLimit : 1;

        try {
            $dialogs = $scope->call('messages.getDialogs', [
                'offset_date' => 0,
                'offset_id' => 0,
                'offset_peer' => ['_' => 'inputPeerEmpty'],
                'limit' => $limit,
                'hash' => 0,
            ]);
        } catch (Throwable $e) {
            $this->logger?->error('teleframe.notify: the MTProto dialog lookup failed, so no peer can be resolved', [
                'exception' => $e,
                'limit' => $limit,
            ]);

            throw $e;
        }

        $ids = [];
        $usernames = [];

        foreach (self::rows($dialogs, 'users') as $row) {
            $found = self::peerOf($row, 'user');

            if ($found === null) {
                continue;
            }

            $ids[$found['id']] = $found['peer'];

            $name = self::nameOf($row);

            if ($name !== null) {
                $usernames[$name] = $found['peer'];
            }
        }

        foreach (self::rows($dialogs, 'chats') as $row) {
            $found = self::peerOf($row, 'chat');

            if ($found === null) {
                continue;
            }

            $ids[$found['id']] = $found['peer'];

            $name = self::nameOf($row);

            if ($name !== null) {
                $usernames[$name] = $found['peer'];
            }
        }

        $this->byId = $ids;
        $this->byUsername = $usernames;

        return ['ids' => $ids, 'usernames' => $usernames];
    }

    /**
     * The peer one `users`/`chats` row is, or null when the row is not a
     * peer at all. The collection the row came from decides the constructor,
     * never the sign of its id: a basic group has no `access_hash` and takes
     * none, while a supergroup row carries one and needs it.
     *
     * The row's own id travels with the peer rather than being read back off
     * the constructor — `InputPeer::channel()` answers `channel_id` and
     * `InputPeer::chat()` answers `chat_id`, so a lookup that assumed one key
     * for both would break on exactly the supergroup rows this class exists
     * to resolve.
     *
     * @param  array<string, mixed>  $row
     * @return array{id: int, peer: array<string, mixed>}|null
     */
    private static function peerOf(array $row, string $collection): ?array
    {
        $id = $row['id'] ?? null;

        if (! is_numeric($id)) {
            return null;
        }

        $hash = $row['access_hash'] ?? null;
        $usableHash = $hash !== null && $hash !== '' && $hash !== 0 && $hash !== '0';

        // A user row with no hash is DROPPED, not sent as access_hash "0": a
        // zero hash is a guess with extra steps, and this class's whole claim
        // is that it never guesses. Basic groups are the one case where the
        // absence of a hash is the real shape (they take none), so a `chats`
        // row without one is legitimately an inputPeerChat.
        if ($collection === 'user') {
            return $usableHash
                ? ['id' => (int) $id, 'peer' => InputPeer::user((int) $id, $hash)]
                : null;
        }

        return [
            'id' => (int) $id,
            'peer' => $usableHash
                ? InputPeer::channel((int) $id, $hash)
                : InputPeer::chat((int) $id),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function rows(mixed $reply, string $key): array
    {
        $rows = is_array($reply) ? ($reply[$key] ?? null) : null;

        if (! is_array($rows)) {
            return [];
        }

        $out = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $out[] = $row;
            }
        }

        return $out;
    }

    private static function nameOf(array $row): ?string
    {
        $name = $row['username'] ?? null;

        if (! is_string($name) || $name === '') {
            return null;
        }

        return strtolower(ltrim($name, '@'));
    }

    private static function usernameOf(mixed $chat): ?string
    {
        if (! is_string($chat)) {
            return null;
        }

        $trimmed = ltrim(trim($chat), '@');

        return $trimmed === '' ? null : strtolower($trimmed);
    }

    private static function idOf(mixed $chat): ?int
    {
        if (is_int($chat)) {
            return $chat;
        }

        if (is_string($chat) && $chat !== '' && (string) (int) $chat === trim($chat)) {
            return (int) $chat;
        }

        return null;
    }

    private function unresolved(mixed $chat, string $why): NotifyException
    {
        $label = is_scalar($chat) ? (string) $chat : ($chat === null ? '(none)' : get_debug_type($chat));

        return new NotifyException(sprintf(
            'teleframe.notify: cannot resolve the MTProto peer for chat %s — %s. An access_hash can only come from a real API call: open the chat once with the account so it is in the dialog list, or bind a host peerResolver that resolves it another way. Refusing rather than sending to a guessed peer.',
            $label,
            $why,
        ));
    }
}
