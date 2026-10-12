<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Methods;

use BadMethodCallException;
use MeRezaRezaei\Teleframe\Bot\Methods\Generated\Bots;
use MeRezaRezaei\Teleframe\Core\Methods\Generated\Account;
use MeRezaRezaei\Teleframe\Core\Methods\Generated\Auth;
use MeRezaRezaei\Teleframe\Core\Methods\Generated\Contacts;
use MeRezaRezaei\Teleframe\Core\Methods\Generated\Help;
use MeRezaRezaei\Teleframe\Core\Methods\Generated\Messages;
use MeRezaRezaei\Teleframe\Core\Methods\Generated\Users;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use Throwable;

/**
 * The class a host holds when it wants to reach a Telegram method BY NAME.
 *
 * It has two faces, and the difference between them is the whole point:
 *
 * 1. THE CURATED BUILDER FACE (fluent sugar over 30 methods):
 *
 *        Methods::messages()->sendMessage()->peer([...])->message('hi')->randomId(1)->toRequest()
 *        Methods::bots()->sendMessage()->chatId('@channel')->text('hi')->toRequest()
 *
 *    Generated from config/curated-methods.json. Thirty entries. It is
 *    CONVENIENCE, never a ceiling: nothing in the call path consults the
 *    curated list, so no host is limited to those thirty names.
 *
 * 2. THE RAW-NAME FACE (every packaged method, curated or not):
 *
 *        Methods::isCallable('account.getWallPapers')  // true
 *        Methods::describe('account.getWallPapers')   // api, crc32, params, layers
 *        $user->call('account.getWallPapers', []);    // UserAccountScope
 *        Teleframe::dispatch(['_' => 'account.getWallPapers']);
 *
 *    "Whatever telegram activity is needed over an mtproto call" is answered
 *    HERE, and the ceiling is the packaged schema catalogue plus the TL
 *    mirror loader — not a hand-maintained list. The reachable set is DERIVED
 *    (see isCallable/describe/all below), so a method Telegram ships that we
 *    never curated is still callable by name today.
 *
 * WHY THE RAW-NAME PREDICATE IS EXACT. Two packaged artefacts decide it, and
 * the two production call paths already use the same two:
 *
 *   - the schema catalogue (MethodRegistry) is the list of API FUNCTIONS. It
 *     is what TeleframeClient::dispatch() resolves `apiOf()` against, so it is
 *     also what decides mtproto-vs-bot-http routing.
 *   - the TL mirror loader (TLRegistry) is what supplies a method's ENCODABLE
 *     SIGNATURE. It indexes constructors — functions AND types — which is why
 *     "does TLRegistry know this name" is NOT the right question: it answers
 *     yes for `userProfilePhoto`, a TYPE you cannot call. A previous resolver
 *     shipped that superset and called it reachability, with a green test
 *     pinning it. It was wrong and is gone.
 *
 *   isCallable() therefore requires BOTH: catalogued (it is a function) and
 *   sendable. "Sendable" is per transport, and conflating the two is a lie in
 *   one direction or the other:
 *     - mtproto names must ALSO be signed, because the call is TL-encoded. A
 *       catalogued function the mirror never got a signature for
 *       (`communities.*`, `ephemeral.*`) fails here, and describe() returns
 *       null instead of pretending — MTProto Client::assertEncodable() rejects
 *       those in live mode too, so the answer here is the answer the wire gives.
 *     - bot-http names need no signature, because the Bot API is JSON over
 *       HTTP and nothing about them is TL-encoded.
 *
 *   A TYPE fails the first test on either transport.
 *
 * LAYERS, REPORTED HONESTLY: two different numbers, on purpose. The wire
 * advertises `EncryptedConnection::LAYER` (227). The vendored mirror and the
 * schema catalogue are layer 229. describe() never blends them: it returns
 * `wire_layer` and `schema_layer` under their own names, and 0 for an
 * unreadable manifest means "unknown", never "same as the wire".
 *
 * Zero-regex, static, plain-PHP-constructible, no container, nothing dials.
 */
final class Methods
{
    public static function messages(): Messages
    {
        return new Messages;
    }

    public static function users(): Users
    {
        return new Users;
    }

    public static function contacts(): Contacts
    {
        return new Contacts;
    }

    public static function account(): Account
    {
        return new Account;
    }

    public static function auth(): Auth
    {
        return new Auth;
    }

    public static function help(): Help
    {
        return new Help;
    }

    /**
     * Can this name be called — as opposed to merely existing?
     *
     * True only when the name is a catalogued API function AND it can actually
     * be sent, which is exactly the conjunction the production call paths
     * enforce:
     *   - TeleframeClient::dispatch() needs it in the catalogue (apiOf) to
     *     route at all;
     *   - the mtproto branch of it, and UserAccountScope::call(), additionally
     *     need an encodable TL signature (Client::assertEncodable) — 34
     *     catalogued entries have none and can never be serialized in any mode.
     *     Those 34 are the 23 layer-gap methods (9 `communities.*`, 8
     *     `ephemeral.*`, 3 `auth.*FirebasePnv*`, 3 `messages.*`) PLUS the 9
     *     generic request envelopes the client does not hand-build. Note the
     *     count: it is NOT `communities.* + ephemeral.*`, which is 17 of the
     *     23, and it is NOT the 36 below;
     *   - the bot-http branch needs no signature: the Bot API is HTTP, not TL.
     *
     * 34 AND 36 ARE BOTH TRUE, AND THIS IS THE ONLY PLACE THEY ARE TOLD APART.
     * `describe()` below states the 36; this method states the 34. They are
     * different questions and the difference is exactly `initConnection` and
     * `invokeWithLayer`, the two envelopes the client hand-builds: real
     * constructors that serialize on every call, so this method answers TRUE
     * for them, while `MethodRegistry::isCallable()` answers FALSE because a
     * host still cannot dial them. Never quote one where the other belongs —
     * that conflation is what made "34" read as the whole boundary.
     * `tests/Schema/CatalogueBoundaryIsEnforcedTest` pins the relationship,
     * so a schema update cannot quietly move either figure.
     *
     * A constructor TYPE (`userProfilePhoto`, `auth.sentCode`) is catalogued as
     * a type, not a function, and answers false — you cannot call a type.
     *
     * Not a curated list: derived from the packaged schema catalogue and the
     * loaded TL mirror on every call.
     */
    public static function isCallable(string $method): bool
    {
        return self::describe($method) !== null;
    }

    /**
     * Everything a host needs to build the call, or null when the name is not
     * callable. Never guesses: `crc32` is the id TLRegistry actually parsed,
     * `source` is the canonical line it parsed it from.
     *
     * For a `bot-http` name there is no TL encoding involved, so `crc32` is 0
     * and `source` is '' — the Bot API carries no constructor id, and reporting
     * one would be an invention rather than a derivation.
     *
     * @return array{
     *     name: string,
     *     api: string,
     *     crc32: int,
     *     source: string,
     *     return_type: string,
     *     wire_layer: int,
     *     schema_layer: int,
     *     params: list<array{name: string, type: string, flag_word: string|null, bit: int|null}>,
     *     docs: string,
     *     description: string,
     *     errors: list<string>
     * }|null
     */
    public static function describe(string $method): ?array
    {
        // 1. Is it a FUNCTION the packaged catalogue knows? (A TYPE fails here.)
        if (! MethodRegistry::has($method)) {
            return null;
        }

        $entry = MethodRegistry::get($method);

        // 2. Can it actually be sent? This differs BY TRANSPORT, and getting
        //    it wrong in either direction lies to the host:
        //
        //    - 'bot-http'  — the Bot API is a JSON/HTTP endpoint. Nothing is
        //      TL-encoded, so a catalogued entry is callable as it stands.
        //    - 'mtproto'   — the call IS TL-encoded, so the mirror must hold
        //      an encodable signature.
        //
        //      TWO HONEST COUNTS, and conflating them is how this comment once
        //      said "36 catalogued methods have none" — which is false, because
        //      36 is the DIAL count and this is the ENCODE count:
        //        * 36 methods a host cannot DIAL BY NAME, in three families with
        //          three different causes — 23 absent from the wire's layer 227
        //          entirely (9 `communities.*`, 8 `ephemeral.*`, 3
        //          `auth.*FirebasePnv*`, 3 `messages.*`), 11 generic request
        //          envelopes the client hand-builds, 2 handshake service
        //          requests.
        //        * 34 methods that cannot be SERIALISED at all.
        //      The delta is exactly `invokeWithLayer` and `initConnection`: real
        //      constructors the client hand-builds on every call, so they DO
        //      serialise, while a host still cannot dial them by name. Both
        //      numbers are now asserted against each other by
        //      tests/Schema/CatalogueBoundaryIsEnforcedTest.php, so a schema
        //      change that moves either one fails here rather than in prose.
        //      The families below describe the DIAL set; of those 36, all 34
        //      unserialisable ones fail
        //      here, on the same gate Client::assertEncodable() applies
        //      before it dials. Ask WireSchemaCoverage::unsupported() for the
        //      live list, or run `teleframe:schema-audit`; do not transcribe
        //      these numbers, they have been wrong in four places.
        //      tests/Schema/CatalogueBoundaryIsEnforcedTest re-derives all
        //      three families from the committed sources, pins every name, and
        //      checks the boundary numbers quoted in prose — so a schema update
        //      that moves any of these fails instead of rotting.
        $signature = '';
        $crc32 = 0;
        if ($entry->api === 'mtproto') {
            try {
                $signature = TLRegistry::signature($method);
                $crc32 = TLRegistry::id($method);
            } catch (Throwable) {
                return null;
            }
        }

        return [
            'name' => $method,
            // 'mtproto' | 'bot-http' — the same apiOf() TeleframeClient::dispatch()
            // routes on, so a host can tell which transport owns the name.
            'api' => $entry->api,
            'crc32' => $crc32,
            'source' => $signature,
            'return_type' => $entry->returnType,
            // the number the connection puts on the wire
            'wire_layer' => EncryptedConnection::LAYER,
            // the number the signature and crc32 above actually came from
            'schema_layer' => TLRegistry::catalogueLayer(),
            'params' => array_map(
                static fn (array $p): array => [
                    'name' => $p['name'],
                    'type' => $p['type'],
                    'flag_word' => $p['flag_word'],
                    'bit' => $p['bit'],
                ],
                $entry->params
            ),
            'docs' => $entry->docs,
            'description' => $entry->description,
            'errors' => $entry->errors,
        ];
    }

    /**
     * Resolve groups that exist but have no explicit accessor yet (added by
     * a later curated-list regeneration); anything unknown fails loudly.
     */
    public static function __callStatic(string $name, array $arguments): object
    {
        $class = ($name === 'bots' ? 'MeRezaRezaei\\Teleframe\\Bot\\Methods\\Generated\\Bots' : 'MeRezaRezaei\\Teleframe\\Core\\Methods\\Generated\\'.ucfirst($name));
        if (class_exists($class)) {
            return new $class;
        }

        throw new BadMethodCallException(
            "Methods::{$name}() is not a generated builder group. Add its methods to config/curated-methods.json and run php bin/generate-method-builders.php."
        );
    }
}
