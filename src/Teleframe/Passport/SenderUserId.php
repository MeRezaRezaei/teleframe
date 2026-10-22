<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * SenderUserId — the ONE place the module reads a sender's telegram user id
 * off an update, for both login chains.
 *
 * WHY THIS CLASS EXISTS: both `PassportLoginService` and
 * `PassportAnonymousLoginService` carry a sender step, and each grew its own
 * private copy of the same reader. Two copies of a security predicate is the
 * failure mode this module keeps paying for (a predicate that existed twice
 * and drifted, a claim that lived in two docblocks and one of them was
 * untrue), so the reader is single-sourced here and both chains call it.
 *
 * ══ WHY THE FIRST VERSION OF IT WAS WRONG ═══════════════════════════════
 *
 * The original reader took `$update['message']['from']['id']` and nothing
 * else. That IS the Bot API spelling — `Message.from` is a `User`. It is NOT
 * an MTProto one, and this is the coordinate whose absence made the whole
 * §6 MTProto claim channel unusable: api.tl:121/122 spell the sender
 * `from_id:flags.8?Peer`, and there is no `from` sub-object at all. So a real
 * `updateNewMessage` resolved to 0, both chains threw "requires the sender
 * telegram user id", and every MTProto-shaped login was refused no matter how
 * correct its claims were. The suite did not catch it because its MTProto
 * fixture had `message.from.id` manufactured onto it — the Bot API spelling
 * bolted onto an MTProto frame, which is the shape a real one can never have.
 *
 * ══ THE COORDINATES, AND WHAT COUNTS AS ONE ═════════════════════════════
 *
 *   - Bot API — `message.from.id`: a `User` object; the field name IS the
 *     coordinate. Unchanged, and still first.
 *   - MTProto — `message.from_id` (camel `fromId`) on a real `updateNewMessage`
 *     where `message` is the `Message`/`messageService` itself, and
 *     `messageService.from_id` / `message_service.from_id` on the
 *     host-adapted spelling the intake itself already reads the action from
 *     (`EncryptedCredentials::sentValuesAction()`). The value is a `Peer`, and
 *     the peer constructor is the WHOLE point:
 *
 *       · `peerUser#59511722 user_id:long` → the user id. The only Peer that
 *         names a user, and passport is user-only.
 *       · `peerChat` → the `sender_chat` this module refuses, in the MTProto
 *         spelling. Refused here exactly as it is on the Bot API side.
 *       · `peerChannel` → a broadcast, not a sender. Refused.
 *
 *     A `Peer` with NO constructor is refused rather than read: a bare bag of
 *     keys that looks like a peer may be a channel, and the constructor is
 *     what makes a TL object the object it claims to be. This is the same
 *     fail-closed rule `SecureValueTraversal` applies to a `SecureValue`
 *     whose type it cannot name. (The generated DTO spelling is accepted
 *     through the same rule — `PeerUserData` → `peerUser` — which trusts the
 *     generated type as the other readers here already do.)
 *
 * The coordinates are tried in the order above and the FIRST one that yields
 * a positive id wins; a shape that yields none returns 0, which both chains
 * already treat as a LOUD refusal naming the type, never a fallback.
 *
 * Zero regex (`ltrim` for the digit-string test, string funcs only) because
 * `src/Teleframe` is a strictly zero-regex tree. Plain-PHP constructible
 * (standalone-smoke posture): a private constructor and one static method, no
 * container, no config, no filesystem.
 *
 * SECURITY: this reads an IDENTIFIER, never a credential — no value it
 * touches is personal data, and nothing here logs or echoes anything.
 */
final class SenderUserId
{
    /** `peerUser#59511722 user_id:long` — the only Peer that names a user. */
    private const PEER_USER = 'peerUser';

    private const GENERATED_SUFFIX = 'Data';

    private function __construct() {}

    /**
     * The sender's telegram user id, or 0 when the update names no user
     * sender in any documented spelling. Never negative.
     *
     * @param  array<string, mixed>  $update
     */
    public static function resolve(array $update): int
    {
        $botApi = self::digitId(self::field($update['message']['from'] ?? null, 'id', 'id'));
        if ($botApi > 0) {
            return $botApi;
        }

        $peers = [
            self::field($update['message'] ?? null, 'from_id', 'fromId'),
            self::field($update['messageService'] ?? null, 'from_id', 'fromId'),
            self::field($update['message_service'] ?? null, 'from_id', 'fromId'),
        ];

        foreach ($peers as $peer) {
            $userId = self::peerUserId($peer);
            if ($userId > 0) {
                return $userId;
            }
        }

        return 0;
    }

    /**
     * The `user_id` of a `peerUser` Peer, or 0. Any other Peer — a chat, a
     * channel, or a node that names nothing at all — is 0, which the chains
     * refuse loudly.
     */
    private static function peerUserId(mixed $peer): int
    {
        if (self::constructorOf($peer) !== self::PEER_USER) {
            return 0;
        }

        return self::digitId(self::field($peer, 'user_id', 'userId'));
    }

    /**
     * A telegram id: an int, or a digit string (the Bot API serialises a
     * user id as a JSON number, the MTProto wire as a `long`, and a host may
     * hand over either as a string). Zero regex: the digit-string test is a
     * plain `ltrim()`. Anything else — a float, a bool, a float-ish string,
     * a blank string — is 0, i.e. refused.
     */
    private static function digitId(mixed $id): int
    {
        if (is_int($id)) {
            return $id > 0 ? $id : 0;
        }

        if (is_string($id) && $id !== '' && ltrim($id, '0123456789') === '') {
            $asInt = (int) $id;

            return $asInt > 0 ? $asInt : 0;
        }

        return 0;
    }

    /**
     * One field of a node, in either spelling: the TL snake_case name for a
     * wire array, the camelCase property name for a generated DTO.
     */
    private static function field(mixed $node, string $snake, string $camel): mixed
    {
        if (is_array($node)) {
            return $node[$snake] ?? $node[$camel] ?? null;
        }

        if (is_object($node)) {
            if (isset($node->{$camel})) {
                return $node->{$camel};
            }

            return $node->{$snake} ?? null;
        }

        return null;
    }

    /**
     * The constructor name of a node: the `_` wire key, or the generated
     * DTO's own class name with the `Data` suffix dropped and the first letter
     * lower-cased (`PeerUserData` → `peerUser`). Null when the node carries no
     * constructor at all — which is a refusal, never a wildcard.
     */
    private static function constructorOf(mixed $node): ?string
    {
        if (is_array($node)) {
            $name = $node['_'] ?? null;

            return is_string($name) && $name !== '' ? $name : null;
        }

        if (! is_object($node)) {
            return null;
        }

        $short = $node::class;
        $cut = strrpos($short, '\\');
        if ($cut !== false) {
            $short = substr($short, $cut + 1);
        }

        if (str_ends_with($short, self::GENERATED_SUFFIX)) {
            $short = substr($short, 0, -strlen(self::GENERATED_SUFFIX));
        }

        if ($short === '') {
            return null;
        }

        return lcfirst($short);
    }
}
