<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

/**
 * Realistic decoded payloads for the A slice of the update-constructor universe:
 * every layer-227 `updateXxx` whose first character after `update` uppercases to
 * `A`.
 *
 * MEASURED COUNT: 3 (A = 3). Measured over the constructor map, never over a grep —
 * a text grep also matches RPC METHODS such as `updates.getDifference`, so it would
 * inflate the denominator with things that are not constructors.
 *
 * Command that produced the set (run from the repo root):
 *
 *   timeout -k 5 180 php -r 'require "vendor/autoload.php";
 *     $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile(__DIR__."/schema/sources/TL_telegram_v227.tl");
 *     $n = []; foreach ($s->types() as $t) { foreach (array_keys((array) $t->constructors()) as $k) { $n[(string)$k] = true; } }
 *     $u = array_values(array_filter(array_keys($n), fn($k) => str_starts_with($k, "update")));
 *     sort($u);
 *     foreach ($u as $k) { if (strtoupper(substr($k,6,1)) === "A") echo $k, "\n"; }'
 *
 * Its verbatim output:
 *
 *   updateAiComposeTones
 *   updateAttachMenuBots
 *   updateAutoSaveSettings
 *   EXIT=0
 *
 * Cross-measured the parameter count of each from the same parsed scheme
 * (`TlConstructor::allParams()`), because the payloads below are empty by
 * MEASUREMENT rather than by omission:
 *
 *   total update ctors: 175
 *   A slice: 3
 *   updateAiComposeTones     params=0
 *   updateAttachMenuBots     params=0
 *   updateAutoSaveSettings   params=0
 *
 * ALL THREE ARE FIELDLESS. The layer-227 source declares each as a bare
 * `= Update;` with no fields and no flags bitmask:
 *
 *   schema/sources/TL_telegram_v227.tl:410  updateAttachMenuBots#17b7a20b = Update;
 *   schema/sources/TL_telegram_v227.tl:422  updateAutoSaveSettings#ec05b097 = Update;
 *   schema/sources/TL_telegram_v227.tl:469  updateAiComposeTones#8c0f91fb = Update;
 *
 * These three are pure INVALIDATION signals — the server telling the client that
 * some other object it already holds is now stale, with the identity implied by
 * the constructor name itself and nothing on the wire. There is genuinely no
 * `flags` mask and no payload field to fake. So a fixture here is the bare root
 * node `['_'] => '<ctor>'` and nothing more: inventing a `bots` vector, an
 * `autoSaveSettings` object or a `tones` object would put keys on the wire that
 * this constructor does not declare, and a decoder reading them would be tested
 * against a message Telegram never sends. This is the same shape the landed
 * sibling uses for `updatePtsChanged` — a flagless ctor gets a flagless fixture.
 *
 * The related types that DO carry the data live elsewhere in the scheme and are
 * reached by a REQUEST (e.g. `attachMenuBots#3c4301c0 hash:long bots:Vector<AttachMenuBot>
 * users:Vector<User>` at `TL_telegram_v227.tl:1540`), never by this update — so
 * they are deliberately absent rather than inlined here.
 *
 * Accessors actually used: `TlScheme::types()` -> `TlType::constructors()`,
 * and for the parameter count `TlConstructor::allParams()`.
 *
 * SHAPE CONVENTION (identical to tests/Ingest/Concerns/HasNestedUpdateFixtures.php,
 * and to the landed GroupMN / GroupPRQ / GroupTUW / GroupFGHIJL siblings):
 *  - `_` carries the BARE constructor name on EVERY constructor node, nested ones
 *    included; no `#hex` ids anywhere in a fixture. The `#hex` form above is the
 *    TL DECLARATION, cited for provenance only — never written into a payload,
 *    which is exactly what `Loader::assertWireShaped()` rejects.
 *  - set-flag booleans arrive as EXPLICIT keys beside the `flags` bitmask that
 *    turns them on (`'flags' => (1 << 0), 'blocked' => true`). No fixture in this
 *    group has a flag, because no constructor in this slice declares one.
 *  - vectors are PHP lists; peers are objects
 *    (`['_','peerUser','user_id' => N]`, `peerChannel`/`channel_id`, `peerChat`/`chat_id`).
 *    None appear here, because none of these three constructors takes a peer.
 *  - only fields the ctor actually DECLARES appear; a flags-conditioned field is
 *    present only when its bit IS set in the mask provided. With a zero-field ctor
 *    there is nothing to gate.
 *  - every value is SYNTHETIC: no session strings, no real `access_hash`, no
 *    credentials, `.env` never read. This slice happens to need no id at all.
 *  - ids are distinct per endpoint, so nothing collides in the peer-scoped key
 *    (account_id, peer_type, peer_id, id) — vacuous here, since these ctors carry
 *    no id; recorded so the property is not mistaken for an omission.
 *
 * SELF-CHECK: `count(self::payloads()) === 3`, and every key has `A` at index 6.
 * Zero endpoints outside `A`. Owned ctors NOT provided: none.
 */
trait GroupA
{
    /**
     * The letters this group owns — the character at index 6 of the constructor
     * name (the first after the literal `update`), uppercased.
     *
     * @return list<string>
     */
    public static function letters(): array
    {
        return ['A'];
    }

    /**
     * @return array<string, array<string, mixed>> ctor name => a decoded payload
     */
    public static function payloads(): array
    {
        return [
            // A — 3 constructors, all FIELDLESS in layer-227
            // (`= Update;` with no fields and no flags bitmask).
            // These are invalidation signals, so the root node alone is the whole
            // wire payload. Adding a field here would be inventing wire data.

            'updateAiComposeTones' => [
                '_' => 'updateAiComposeTones',
            ],
            'updateAttachMenuBots' => [
                '_' => 'updateAttachMenuBots',
            ],
            'updateAutoSaveSettings' => [
                '_' => 'updateAutoSaveSettings',
            ],
        ];
    }
}
