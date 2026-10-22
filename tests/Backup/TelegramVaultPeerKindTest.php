<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;
use MeRezaRezaei\Teleframe\Tests\Support\FakeVaultApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * F12-2 and F12-3 — the two halves of the V1.2 claim that were measured
 * absent at HEAD (audit 2026-10-15 §3):
 *
 *  - F12-2: "treat the same thing with channels AND groups". The vault was
 *    broadcast-only — the dialog filter skipped every chat whose `_` was not
 *    'channel' and the sole creator was a broadcast channels.createChannel.
 *  - F12-3: "the metadata of them will be the message context … a coding
 *    string so telegram cannot see what is inside". Every chunk caption was
 *    one constant, so a chunk document said nothing about itself.
 *
 * These run against the offline fake api map, so what they prove is the
 * VAULT's contract: which peer it addresses, how it resolves one, and what
 * it writes into a caption. They do NOT prove Telegram accepts a supergroup
 * as a vault destination — that is F12-4's live gate, which does not exist.
 */
final class TelegramVaultPeerKindTest extends TestCase
{
    private const PASSPHRASE = 'correct horse battery staple';

    // vendor/ is a symlink to the main tree: pin this tree's (fixed)
    // TelegramVault sources before any test body autoloads them.
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 2).'/tests/Schema/FixesAutoload.php';
    }

    /** @return array{call: string, args: list<mixed>} the last recorded $name call */
    private function callOf(FakeVaultApi $api, string $name): ?array
    {
        $match = null;
        foreach ($api->calls as $call) {
            if ($call['call'] === $name) {
                $match = $call;
            }
        }

        return $match;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function callsOf(FakeVaultApi $api, string $name): array
    {
        $matches = [];
        foreach ($api->calls as $call) {
            if ($call['call'] === $name) {
                $matches[] = $call;
            }
        }

        return $matches;
    }

    /**
     * The recorded scope calls for one MTProto method, in call order.
     *
     * @return list<array<string, mixed>>
     */
    private static function callsOfMethod(DialogScope $scope, string $method): array
    {
        $matches = [];
        foreach ($scope->calls as $call) {
            if ($call['method'] === $method) {
                $matches[] = $call;
            }
        }

        return $matches;
    }

    /**
     * @param  list<array<string, mixed>>  $calls
     * @return list<mixed>
     */
    private static function argumentOfEach(array $calls, int $index): array
    {
        $values = [];
        foreach ($calls as $call) {
            $values[] = $call['args'][$index];
        }

        return $values;
    }

    /**
     * @param  list<array<string, mixed>>  $calls
     * @return list<mixed>
     */
    private static function flagOfEach(array $calls, string $flag): array
    {
        $values = [];
        foreach ($calls as $call) {
            $values[] = $call['params'][$flag];
        }

        return $values;
    }

    // ------------------------------------------------------------------
    // F12-2 — channels AND groups
    // ------------------------------------------------------------------

    public function test_group_kind_is_accepted_and_creates_a_peer_of_that_kind(): void
    {
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE, TelegramVault::PEER_GROUP);

        $vault->putManifest('{"version":1}');

        self::assertSame(1, $api->createChannelCalls());
        $create = $this->callOf($api, 'createChannel');
        self::assertNotNull($create);
        self::assertSame(TelegramVault::PEER_GROUP, $create['args'][2], 'the creator is told WHICH kind of peer to mint');
        self::assertSame('group', $api->peerKind(1000));
    }

    public function test_channel_kind_still_creates_a_broadcast_peer(): void
    {
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE, TelegramVault::PEER_CHANNEL);

        $vault->putManifest('{"version":1}');

        $create = $this->callOf($api, 'createChannel');
        self::assertNotNull($create);
        self::assertSame(TelegramVault::PEER_CHANNEL, $create['args'][2]);
        self::assertSame('channel', $api->peerKind(1000));
    }

    public function test_existing_group_is_found_and_not_recreated(): void
    {
        $api = new FakeVaultApi;
        $api->addChannel(TelegramVault::channelTitle('set1'), 'group');
        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE, TelegramVault::PEER_GROUP);

        $vault->putChunk('hash-a', 'bytes-a');

        self::assertSame(0, $api->createChannelCalls(), 'an existing group must be reused, not re-minted');
        $send = $this->callOf($api, 'sendDocument');
        self::assertNotNull($send);
        // A supergroup is a `channel` TL object and dials as inputPeerChannel.
        self::assertSame(
            ['_' => 'inputPeerChannel', 'channel_id' => 1000, 'access_hash' => 4242],
            $send['args'][0]
        );
    }

    public function test_a_channel_kind_vault_never_adopts_a_group_of_the_same_title(): void
    {
        // The old filter skipped every non-'channel' chat; the new one must
        // not go to the other extreme and hand a CHANNEL vault a supergroup
        // that happens to share the title. A supergroup is a `channel` TL
        // object, so the megagroup flag is the ONLY thing separating them.
        $api = new FakeVaultApi;
        $api->addChannel(TelegramVault::channelTitle('set1'), 'group');

        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE, TelegramVault::PEER_CHANNEL);
        $vault->putManifest('{"version":1}');

        self::assertSame(1, $api->createChannelCalls(), 'the title collision must not hijack the channel vault');
        self::assertSame('channel', $api->peerKind(1001), 'a broadcast peer was minted, not the group');
    }

    public function test_a_group_kind_vault_never_adopts_a_broadcast_channel_of_the_same_title(): void
    {
        $api = new FakeVaultApi;
        $api->addChannel(TelegramVault::channelTitle('set1'), 'channel');

        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE, TelegramVault::PEER_GROUP);
        $vault->putManifest('{"version":1}');

        self::assertSame(1, $api->createChannelCalls());
        self::assertSame('group', $api->peerKind(1001));
    }

    public function test_a_legacy_basic_group_dials_as_input_peer_chat(): void
    {
        // `{_:'chat'}` is the legacy basic group: a different TL constructor
        // from a supergroup, so it needs inputPeerChat, not inputPeerChannel.
        $api = new FakeVaultApi;
        $api->addChannel(TelegramVault::channelTitle('set1'), 'group', null, 'chat');

        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE, TelegramVault::PEER_GROUP);
        self::assertSame(['_' => 'inputPeerChat', 'chat_id' => 1000], $vault->inputPeer());

        $vault->putChunk('hash-a', 'bytes-a');
        self::assertSame('bytes-a', $vault->getChunk('hash-a'), 'chunks round-trip through a basic group peer');
    }

    public function test_peer_resolves_by_id_not_only_by_title(): void
    {
        // The audit's second half: "resolve by id as well as title". A peer
        // renamed after it was configured must still be reachable by id.
        $api = new FakeVaultApi;
        $groupId = $api->addChannel('a completely different title', 'group', 777);

        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE, TelegramVault::PEER_GROUP, $groupId);
        $vault->putManifest('{"version":1}');

        self::assertSame(0, $api->createChannelCalls(), 'the named id resolved; nothing was minted');
        self::assertSame(['_' => 'inputPeerChannel', 'channel_id' => $groupId, 'access_hash' => 4242], $vault->inputPeer());
    }

    public function test_an_invisible_named_peer_id_fails_loud_instead_of_minting_another(): void
    {
        // An operator who named a peer asked for THAT peer. Silently creating
        // a different destination would archive to a place nobody is reading
        // and report success.
        $api = new FakeVaultApi;

        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE, TelegramVault::PEER_GROUP, 4242);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no dialog of kind "group" with that id is visible');

        $vault->putManifest('{"version":1}');
    }

    public function test_unknown_peer_kind_is_refused_at_construction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('peer_type must be one of channel|group');

        new TelegramVault('set1', (new FakeVaultApi)->map(), self::PASSPHRASE, 'supergroup');
    }

    public function test_peer_type_is_readable_without_inspecting_a_peer_array(): void
    {
        $api = new FakeVaultApi;

        self::assertSame(
            TelegramVault::PEER_GROUP,
            (new TelegramVault('set1', $api->map(), self::PASSPHRASE, TelegramVault::PEER_GROUP))->peerType()
        );
        self::assertSame(
            TelegramVault::PEER_CHANNEL,
            (new TelegramVault('set1', $api->map(), self::PASSPHRASE))->peerType(),
            'the default is unchanged: a broadcast channel',
        );
    }

    // ------------------------------------------------------------------
    // The LIVE call map's own peer logic.
    //
    // The FakeVaultApi map filters by kind ITSELF, so the tests above prove
    // the vault's plumbing but NOT the classifier — reverting
    // matchesPeerType() to the old broadcast-only filter left them all
    // green. These drive scopeApi()'s real findChannel/createChannel
    // closures against a dialog list shaped like a real
    // messages.getDialogs reply, which is where the kind separation
    // actually lives.
    // ------------------------------------------------------------------

    public function test_the_live_dialog_scan_separates_a_supergroup_from_a_broadcast_channel(): void
    {
        $scope = new DialogScope([
            ['_' => 'channel', 'id' => 100, 'title' => 'a broadcast channel', 'access_hash' => 11],
            ['_' => 'channel', 'id' => 200, 'title' => 'a group', 'megagroup' => true, 'access_hash' => 22],
        ]);
        $find = TelegramVault::scopeApi($scope)['findChannel'];

        $asChannel = $find('a group', TelegramVault::PEER_CHANNEL, null);
        self::assertNull($asChannel, 'the megagroup must NOT be handed to a channel-kind vault');

        $asGroup = $find('a group', TelegramVault::PEER_GROUP, null);
        self::assertIsArray($asGroup);
        self::assertSame(200, $asGroup['id']);
        self::assertSame(22, $asGroup['access_hash']);
        self::assertSame('channel', $asGroup['peer'], 'a supergroup is a `channel` object and dials as inputPeerChannel');

        // And the broadcast channel is still reachable as a channel.
        self::assertSame(100, $find('a broadcast channel', TelegramVault::PEER_CHANNEL, null)['id']);
        self::assertNull($find('a broadcast channel', TelegramVault::PEER_GROUP, null), 'and the broadcast channel must NOT be handed to a group-kind vault either');
    }

    public function test_the_live_dialog_scan_reports_a_basic_chat_as_a_chat_constructor(): void
    {
        $scope = new DialogScope([
            ['_' => 'chat', 'id' => 300, 'title' => 'an old basic group', 'access_hash' => 33],
        ]);
        $find = TelegramVault::scopeApi($scope)['findChannel'];

        $found = $find('an old basic group', TelegramVault::PEER_GROUP, null);
        self::assertIsArray($found);
        self::assertSame('chat', $found['peer'], 'a basic group needs inputPeerChat, not inputPeerChannel');

        self::assertNull($find('an old basic group', TelegramVault::PEER_CHANNEL, null));
    }

    public function test_the_live_dialog_scan_resolves_by_id_across_titles(): void
    {
        $scope = new DialogScope([
            ['_' => 'channel', 'id' => 200, 'title' => 'renamed since it was configured', 'megagroup' => true, 'access_hash' => 22],
        ]);
        $find = TelegramVault::scopeApi($scope)['findChannel'];

        $found = $find('teleframe-backup:set1', TelegramVault::PEER_GROUP, 200);
        self::assertIsArray($found, 'an explicit id is an identity and wins over the title');
        self::assertSame(200, $found['id']);

        self::assertNull($find('teleframe-backup:set1', TelegramVault::PEER_GROUP, 999), 'an id that is not there stays not there');
    }

    public function test_the_live_creator_asks_for_a_megagroup_only_for_the_group_kind(): void
    {
        $scope = new DialogScope([]);
        $create = TelegramVault::scopeApi($scope)['createChannel'];

        $create('g', 'about', TelegramVault::PEER_GROUP);
        $create('c', 'about', TelegramVault::PEER_CHANNEL);

        $created = self::callsOfMethod($scope, 'channels.createChannel');

        self::assertSame([true, false], self::flagOfEach($created, 'megagroup'), 'a group is minted as a MEGAGROUP; a channel stays broadcast');
        self::assertSame([false, true], self::flagOfEach($created, 'broadcast'), 'broadcast and megagroup are opposites on the wire');
    }

    // ------------------------------------------------------------------
    // F12-3 — the metadata IS the message context
    // ------------------------------------------------------------------

    public function test_a_chunk_before_any_manifest_carries_the_bare_benign_caption(): void
    {
        // The manifest is posted LAST, so the first run's chunks are all
        // uploaded before any generation exists to describe. The fallback is
        // the bare constant — never a half-formed or forged fragment.
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE);

        $vault->putChunk('hash-a', 'bytes-a');

        $send = $this->callOf($api, 'sendDocument');
        self::assertNotNull($send);
        self::assertSame(TelegramVault::CHUNK_CAPTION, $send['args'][2]);
    }

    public function test_a_chunk_after_a_manifest_carries_a_sealed_context_fragment(): void
    {
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE);

        $vault->putManifest('{"version":1,"generation":7}');
        $vault->putChunk('hash-a', 'bytes-a');

        $send = $this->callOf($api, 'sendDocument');
        self::assertNotNull($send);
        $caption = $send['args'][2];
        self::assertIsString($caption);
        self::assertStringStartsWith(TelegramVault::CHUNK_CAPTION_PREFIX, $caption, 'the benign label stays the readable head');
        self::assertStringNotContainsString('hash-a', $caption, 'the plaintext content address never appears on the wire');
        self::assertStringNotContainsString('"generation"', $caption, 'no field of the context JSON is legible in the caption');
        self::assertStringNotContainsString('set1', $caption, 'the set id is sealed, not spelled out');
        self::assertStringNotContainsString('generation=', $caption);
    }

    public function test_the_chunk_context_opens_under_the_manifest_key_and_names_the_generation(): void
    {
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE);

        $vault->putManifest('{"version":1,"generation":7}');
        $vault->putChunk('hash-a', 'bytes-a');

        $send = $this->callOf($api, 'sendDocument');
        self::assertNotNull($send);
        $caption = $send['args'][2];
        self::assertIsString($caption, 'the chunk caption must be a string the reader can slice');

        $fragment = substr($caption, strlen(TelegramVault::CHUNK_CAPTION_PREFIX));
        self::assertStringStartsWith(TelegramVault::CHUNK_CONTEXT_MARKER, $fragment);
        $sealed = base64_decode(substr($fragment, strlen(TelegramVault::CHUNK_CONTEXT_MARKER)), true);
        self::assertIsString($sealed, 'the fragment must carry valid base64 ciphertext after the marker');

        // Same key the manifest is sealed under: one decoder opens both, and
        // a chunk's context can never be read by a different key than the
        // manifest that claims it.
        $opened = json_decode(
            VaultCrypto::decryptChunk(
                VaultCrypto::deriveManifestKey('set1', self::PASSPHRASE),
                $sealed,
            ),
            true,
        );
        self::assertSame(['set' => 'set1', 'generation' => 7], $opened);
    }

    public function test_a_context_fragment_is_unreadable_under_another_passphrase(): void
    {
        $api = new FakeVaultApi;
        $writer = new TelegramVault('set1', $api->map(), self::PASSPHRASE);
        $writer->putManifest('{"version":1,"generation":7}');
        $writer->putChunk('hash-a', 'bytes-a');

        $send = $this->callOf($api, 'sendDocument');
        self::assertNotNull($send);
        $caption = $send['args'][2];
        self::assertIsString($caption, 'the chunk caption must be a string the reader can slice');
        $sealed = base64_decode(
            substr($caption, strlen(TelegramVault::CHUNK_CAPTION_PREFIX) + strlen(TelegramVault::CHUNK_CONTEXT_MARKER)),
            true
        );
        self::assertIsString($sealed, 'the fragment must carry valid base64 ciphertext');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        VaultCrypto::decryptChunk(VaultCrypto::deriveManifestKey('set1', 'a different passphrase'), $sealed);
    }

    public function test_every_chunk_of_a_run_carries_the_same_context(): void
    {
        // The fragment is written once per generation, not once per chunk:
        // a per-chunk re-seal would be non-deterministic, so two readers of
        // the same generation's chunks could not tell they matched.
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $api->map(), self::PASSPHRASE);

        $vault->putManifest('{"version":1,"generation":7}');
        $vault->putChunk('hash-a', 'bytes-a');
        $vault->putChunk('hash-b', 'bytes-b');

        $captions = self::argumentOfEach(self::callsOf($api, 'sendDocument'), 2);

        self::assertCount(2, $captions, 'both chunks of the run were posted as documents');
        self::assertSame($captions[0], $captions[1], 'one generation, one context, message for message');
    }

}

/**
 * A UserAccountScope double that answers `messages.getDialogs` with a
 * scripted chat list and records every call — enough to drive
 * TelegramVault::scopeApi()'s real peer-classification closures offline.
 *
 * @see \MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope for the base double
 */
final class DialogScope extends FakeUserScope
{
    /**
     * @param  list<array<string, mixed>>  $chats  dialog rows, as messages.getDialogs returns them
     */
    public function __construct(private readonly array $chats)
    {
        parent::__construct();
    }

    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];

        if ($method === 'messages.getDialogs') {
            return ['chats' => $this->chats];
        }

        if ($method === 'channels.createChannel') {
            // A freshly minted peer echoes back as a `channel` object, with
            // the megagroup flag the caller asked for.
            return ['chats' => [[
                '_' => 'channel',
                'id' => 900,
                'title' => $params['title'] ?? '',
                'access_hash' => 4242,
                'megagroup' => (bool) ($params['megagroup'] ?? false),
            ]]];
        }

        return parent::call($method, $params);
    }
}
