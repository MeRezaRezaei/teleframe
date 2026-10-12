<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\Methods;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\Methods\Methods;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use PHPUnit\Framework\TestCase;

/**
 * The owner's declared capability (verbatim 2026-09-24, docs/verbatim/):
 *
 *     "this app must be able to do whatever telegram activity needed using
 *      mtproto call both for user and bot"
 *
 * A prior cycle was titled "Make 'mtproto-all' a real capability, not a curated
 * subset" because a curated subset was masquerading as full coverage. The trap
 * this file exists to keep closed: the reachable set must stay DERIVED from the
 * packaged schema catalogue and the TL mirror loader, never from a hand-kept
 * list of names.
 *
 * WHY THESE TESTS GO THROUGH A PRODUCTION PATH. The previous resolver was
 * covered by a test that called the class directly — so the class could be
 * entirely disconnected from the engine and the suite stayed green, which is
 * exactly how a dead class shipped. Every test below reaches the resolver
 * through something a HOST reaches:
 *
 *   - Methods::isCallable()/describe() (the named seam a host asks before it
 *     dials), and
 *   - TeleframeClient::user()->call() / ->callMany() and
 *     TeleframeClient::dispatch() — the four public routes by raw name, run in
 *     the package's own offline mode so no socket and no container is needed.
 *
 * The concurrency between "is this name callable" and "does the engine accept
 * this name" is the contract. If one route tightens and the other does not,
 * these go red.
 */
final class UncuratedMethodResolutionTest extends TestCase
{
    /**
     * Real methods that exist in the packaged schema and were never curated.
     * `curated-methods.json` is the 30-entry builder-sugar list; none of these
     * is on it, and none needs to be for a host to call one by name.
     *
     * @return list<string>
     */
    private const UNCURATED_MTPROTO = [
        'account.getWallPapers',
        'account.getNotifySettings',
        'messages.getPeerSettings',
        'channels.getChannels',
        'messages.getMessages',
        'help.getAppConfig',
        'messages.getAllStickers',
        'premium.getUserBoosts',
        'stories.getStoriesByID',
        'account.getWallPapers',
    ];

    /**
     * The four raw-name routes a host actually has, each exercised with an
     * UNCURATED method so no test can pass on curated sugar.
     */
    public function test_the_user_scope_call_route_accepts_an_uncurated_method_by_name(): void
    {
        $client = $this->client();

        $result = $client->user()->call('account.getWallPapers', ['hash' => 0]);

        self::assertSame('rpc_result', $result['_'], 'the call was accepted by the engine, not rejected as unknown');
        self::assertTrue($result['_offline'], 'offline marker: nothing was sent, this is not a Telegram answer');
        self::assertSame('account.getWallPapers', $result['method']);
        self::assertSame(['hash' => 0], $result['params']);
    }

    public function test_the_batched_call_route_accepts_several_uncurated_methods_in_one_container(): void
    {
        $client = $this->client();

        $results = $client->callMany([
            ['method' => 'account.getWallPapers', 'params' => ['hash' => 0]],
            ['method' => 'messages.getPeerSettings', 'params' => ['peer' => ['_' => 'inputPeerSelf']]],
            ['method' => 'channels.getChannels', 'params' => []],
        ]);

        self::assertSame(['account.getWallPapers', 'messages.getPeerSettings', 'channels.getChannels'], array_column($results, 'method'));
        self::assertSame([0, 1, 2], array_keys($results), 'keys and input order are preserved');
        foreach ($results as $result) {
            self::assertTrue($result['_offline']);
        }
    }

    public function test_the_dispatch_route_routes_an_uncurated_mtproto_method_to_the_user_scope(): void
    {
        $client = $this->client();

        $result = $client->dispatch(['_' => 'account.getWallPapers', 'hash' => 0]);

        self::assertSame('rpc_result', $result['_']);
        self::assertSame('account.getWallPapers', $result['method']);
        self::assertArrayNotHasKey('_', $result['params'], 'the routing discriminator is consumed, never sent as a param');
    }

    public function test_the_fluent_builder_route_still_covers_the_curated_sugar(): void
    {
        // The curated face is CONVENIENCE, not the ceiling. It must keep
        // working exactly as documented, and it must keep producing a request
        // the raw-name routes above accept — the two faces are one call path.
        $request = Methods::messages()->sendMessage()
            ->peer(['_' => 'inputPeerSelf'])
            ->message('hi')
            ->randomId(7)
            ->toRequest();

        $result = $this->client()->dispatch($request);

        self::assertSame('messages.sendMessage', $result['method']);
        self::assertSame('hi', $result['params']['message']);
    }

    /**
     * The regression this whole file is named for: no hand-list may creep back
     * in as the ceiling. Every uncurated name must be callable, through the
     * engine, right now.
     */
    public function test_every_uncurated_method_is_callable_through_the_engine(): void
    {
        $client = $this->client();

        foreach (self::UNCURATED_MTPROTO as $method) {
            self::assertTrue(
                Methods::isCallable($method),
                "{$method} is in the packaged schema and must be callable by name without being curated"
            );
            self::assertNotNull(Methods::describe($method));

            $result = $client->user()->call($method, []);
            self::assertSame($method, $result['method'], "{$method} must survive the real call path");
        }
    }

    /**
     * The curation is a BOUNDARY, not a capability. 30 curated builder entries
     * cannot be the ceiling on a 700+ method catalogue — if this ever gets
     * close, the curated list has started masquerading as full coverage again.
     */
    public function test_the_reachable_set_is_far_larger_than_the_curated_builder_list(): void
    {
        $catalogue = $this->catalogueMethodCount();
        $curated = $this->curatedBuilderCount();

        self::assertGreaterThan(10, $curated, 'the curated builder list is the sugar surface');
        self::assertGreaterThan(
            $curated * 10,
            $catalogue,
            "packaged schema lists {$catalogue} methods against {$curated} curated builders; if these converge, a curated subset is again wearing a full-coverage label"
        );
    }

    /**
     * A TYPE is not a method. The registry indexes constructors and does not
     * separate functions from types, so "does TLRegistry know this name" is the
     * WRONG question — it answers yes for `userProfilePhoto`. The predicate that
     * ships must require catalogued-as-a-function, and say no for a type.
     *
     * A superseded resolver shipped the superset and had a green test pinning
     * it. This is the test that says the opposite, on purpose.
     */
    public function test_a_constructor_type_is_not_reported_as_a_callable_method(): void
    {
        foreach (['userProfilePhoto', 'auth.sentCode', 'inputPeerChat', 'message', 'storage.fileUnknown'] as $type) {
            self::assertNotSame(
                [],
                array_filter([$type], static fn (string $t): bool => TLRegistry::signature($t) !== ''),
                'precondition: the registry DOES know this name — it is a constructor, not a missing entry'
            );
            self::assertFalse(
                Methods::isCallable($type),
                "{$type} is a TYPE; you cannot call it, and the resolver must not claim otherwise"
            );
            self::assertNull(Methods::describe($type));
        }
    }

    /**
     * A catalogued function the mirror has no signature for cannot be encoded
     * in ANY mode — MTProto Client::assertEncodable() rejects it before it
     * dials. The seam must agree with the wire, and say so rather than
     * pretending a call would work.
     */
    public function test_a_catalogued_method_with_no_encodable_signature_is_not_callable(): void
    {
        foreach (['communities.getPeerLinkRequests', 'ephemeral.secureValueEphemeralKey', 'auth.initFirebasePnvLogin'] as $method) {
            self::assertNull(
                Methods::describe($method),
                "{$method} is catalogued but unsigned, so it can never reach Telegram"
            );
            self::assertFalse(Methods::isCallable($method));
        }

        // ...and the engine agrees, in the same mode the tests run in.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot reach Telegram|wire mirror|ENVELOPE/');
        $this->client()->user()->call('communities.getPeerLinkRequests', []);
    }

    public function test_describe_reports_the_routing_api_so_a_host_knows_the_transport(): void
    {
        $mtproto = Methods::describe('account.getWallPapers');
        $botHttp = Methods::describe('sendMessage');

        self::assertNotNull($mtproto);
        self::assertSame('mtproto', $mtproto['api']);

        self::assertNotNull($botHttp);
        self::assertSame('bot-http', $botHttp['api'], 'the same name family on the bot API routes over HTTP, and the seam says so');
    }

    /**
     * The params a host must pass come from the same artefact the engine encodes
     * against, so the description cannot drift from the call.
     */
    public function test_describe_carries_the_params_and_the_real_wire_id(): void
    {
        $described = Methods::describe('messages.getPeerSettings');

        self::assertNotNull($described);
        $names = array_column($described['params'], 'name');
        self::assertSame(['peer'], $names, 'the packaged catalogue is the source; layer 229 dropped the old `hash` argument');
        self::assertSame(TLRegistry::id('messages.getPeerSettings'), $described['crc32'], 'the id is the registry\'s, never a guess');
        self::assertStringContainsString('messages.getPeerSettings', $described['source']);
        self::assertSame(EncryptedConnection::LAYER, $described['wire_layer']);
        self::assertSame(TLRegistry::catalogueLayer(), $described['schema_layer']);
    }

    /**
     * Two different numbers, deliberately. describe() must never blend them into
     * one `layer` key again — that is what made a green test certify a false
     * claim once already.
     */
    public function test_describe_keeps_the_wire_and_catalogue_layers_apart(): void
    {
        $described = Methods::describe('messages.getHistory');

        self::assertNotNull($described);
        self::assertArrayNotHasKey('layer', $described);
        self::assertSame(227, $described['wire_layer']);
        self::assertSame(229, $described['schema_layer']);
        self::assertNotSame($described['wire_layer'], $described['schema_layer']);
    }

    public function test_an_unknown_name_is_neither_callable_nor_describable(): void
    {
        self::assertFalse(Methods::isCallable('some.methodThatDoesNotExist'));
        self::assertNull(Methods::describe('some.methodThatDoesNotExist'));
    }

    /**
     * Repeated lookups are stable: the predicate is derived from immutable
     * packaged artefacts, so it cannot drift between two calls in one request.
     */
    public function test_callability_is_stable_across_repeated_lookups(): void
    {
        self::assertTrue(Methods::isCallable('updates.getState'));
        self::assertSame(Methods::describe('updates.getState'), Methods::describe('updates.getState'));
        self::assertTrue(Methods::isCallable('updates.getState'));
    }

    /**
     * A plain-PHP client with an offline session: no container, no socket, no
     * credentials. This is the shape a host's own unit test would build.
     */
    private function client(): TeleframeClient
    {
        return new TeleframeClient(
            defaultApiId: 1,
            defaultApiHash: 'h',
            defaultUserSession: 'offline-unit-auth-key',
        );
    }

    private function catalogueMethodCount(): int
    {
        $count = 0;
        foreach (['methods-mtproto.json', 'methods-botapi.json'] as $file) {
            $envelope = json_decode((string) file_get_contents(SchemaArtifacts::path($file)), true);
            $count += count((array) (is_array($envelope) ? ($envelope['methods'] ?? []) : []));
        }

        return $count;
    }

    private function curatedBuilderCount(): int
    {
        $envelope = json_decode(
            (string) file_get_contents(__DIR__.'/../../../src/Schema/config/curated-methods.json'),
            true
        );

        // The file is keyed by transport: ['mtproto' => [...], 'botapi' => [...]],
        // so count the names, never the two top-level keys.
        $count = 0;
        foreach ((array) (is_array($envelope) ? $envelope : []) as $list) {
            $count += count((array) $list);
        }

        return $count;
    }
}
