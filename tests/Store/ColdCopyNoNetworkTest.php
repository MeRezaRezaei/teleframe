<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\RemoteTierContract;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Store\TelegramTier;
use MeRezaRezaei\Teleframe\Tests\Support\FakeStoreApi;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * The cold path needs NO network and NO credentials (V1.2, 2026-09-27) — and
 * that claim is proved structurally, not by a machine that happens to be
 * offline.
 *
 * A suite that says "no network required" because the runner had no network is
 * the false-green shape commits ee21e334 and 97413884 were written about,
 * pointed the other way: it passes on a disconnected laptop and in a CI image
 * with no egress, but it also passes on a developer machine that WOULD have
 * dialled out, so it proves nothing about the code. So this file attacks the
 * claim from both sides:
 *
 *  1. ARMED, not absent. A stream wrapper that throws on stream_open is
 *     registered for every scheme a dial would use, and the test first proves
 *     the guard is live by opening a socket THROUGH it and requiring the
 *     refusal. A guard that silently failed to register would otherwise make
 *     every assertion below vacuous — which is the trap this file exists to
 *     avoid.
 *  2. STRUCTURAL, not incidental. The cold source files are scanned for the
 *     tokens a credentialed or dialling implementation would contain
 *     (getenv, curl, fsockopen, session names, and preg_ while we are here).
 *     This holds in an image with a network and in one without, and it holds
 *     for the code rather than for the run.
 */
final class ColdCopyNoNetworkTest extends TestCase
{
    private const ACCOUNT = 19;

    private const BUCKET = 'data:offline';

    /** Schemes any "just dial it" implementation would use. */
    private const DIAL_SCHEMES = ['tcp', 'ssl', 'http', 'https', 'ftp'];

    /** Tokens a credentialed or dialling implementation would contain. */
    private const BANNED_TOKENS = [
        'getenv(',
        'curl_',
        'fsockopen',
        'stream_socket_client',
        'file_get_contents(',
        'TELEFRAME_SESSION',
        'api_id',
        'api_hash',
        'preg_',
    ];

    /** @var list<string> schemes this run registered over an existing wrapper */
    private array $armed = [];

    /** @var list<string> schemes this run registered where none existed (ssl often) */
    private array $invented = [];

    protected function setUp(): void
    {
        foreach (self::DIAL_SCHEMES as $scheme) {
            $existed = in_array($scheme, stream_get_wrappers(), true);
            if ($existed) {
                stream_wrapper_unregister($scheme);
            }
            self::assertTrue(
                stream_wrapper_register($scheme, ThrowingDialStream::class),
                "could not arm the no-network guard for {$scheme}",
            );
            if ($existed) {
                $this->armed[] = $scheme;
            } else {
                $this->invented[] = $scheme;
            }
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->armed as $scheme) {
            stream_wrapper_restore($scheme);
        }
        foreach ($this->invented as $scheme) {
            // No built-in wrapper existed, so there is nothing to restore —
            // unregistering is the only way back to the machine's state.
            stream_wrapper_unregister($scheme);
        }
        $this->armed = [];
        $this->invented = [];
    }

    public function test_the_no_network_guard_is_actually_armed(): void
    {
        // Without this, every other test in the file could pass on a machine
        // that merely happened to be offline.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must never dial');

        fopen('tcp://127.0.0.1:9', 'rb');
    }

    public function test_the_whole_cold_lifecycle_runs_with_no_network_and_no_credentials(): void
    {
        $api = new FakeStoreApi(scope: 'cold-offline');
        $api->seedFallback = true;

        $channelFor = fn (string $bucket): TelegramStoreChannel => new TelegramStoreChannel($bucket, $api->apiMap());

        // 1. A remote tier, built by a seeding service whose index is then thrown
        //    away: what the cold service sees is Telegram and nothing else.
        $warm = new StoreService(
            accountId: self::ACCOUNT,
            channelFactory: $channelFor,
            index: new MemoryStoreIndex,
            cache: new MemoryHotCache(ttlSeconds: 60, maxBytes: 1024 * 1024),
            queue: new SyncUploadQueue,
        );
        $bytes = str_repeat('offline cold ', 300);
        $warm->put(self::BUCKET, 'offline.bin', $bytes, ObjectManifest::MODE_RAW);

        $channelId = (int) ((new TelegramStoreChannel(self::BUCKET, $api->apiMap()))->bucketInputPeer()['channel_id'] ?? 0);
        $api->seedPeerId = $channelId;
        $msgId = $api->uploadDocument('offline.jpg', 'jpeg bytes');

        // 2. A cold machine: empty index, no cache, no credentials of any kind.
        $coldIndex = new MemoryStoreIndex;
        $cold = new StoreService(
            accountId: self::ACCOUNT,
            channelFactory: $channelFor,
            index: $coldIndex,
            cache: new MemoryHotCache(ttlSeconds: 60, maxBytes: 1024 * 1024),
            queue: new SyncUploadQueue,
            referencePolicy: ReferencePolicy::coldRemoteAuthoritative(),
        );

        $uploadsBefore = $api->uploadPartCalls;
        self::assertNull($coldIndex->findObject(self::ACCOUNT, self::BUCKET, 'offline.bin'));

        // Read, re-publish and revoke — the whole cold path, no socket allowed.
        self::assertSame($bytes, $cold->get(self::BUCKET, 'offline.bin'));
        self::assertTrue($cold->exists(self::BUCKET, 'offline.bin'));
        self::assertSame(strlen($bytes), $cold->size(self::BUCKET, 'offline.bin'));

        $posted = $cold->publishByReference(
            self::BUCKET,
            $msgId,
            ['_' => 'inputPeerChannel', 'channel_id' => 900, 'access_hash' => 4242],
            'offline',
        );
        self::assertGreaterThan(0, $posted);
        self::assertSame($uploadsBefore, $api->uploadPartCalls, 'the cold path uploaded something');

        $cold->delete(self::BUCKET, 'offline.bin');
        self::assertFalse($cold->exists(self::BUCKET, 'offline.bin'));
    }

    public function test_the_cold_source_files_contain_no_dial_and_no_credential(): void
    {
        $classes = [
            StoreService::class,
            TelegramTier::class,
            RemoteTierContract::class,
            ReferencePolicy::class,
            TelegramStoreChannel::class,
        ];

        foreach ($classes as $class) {
            $file = (new ReflectionClass($class))->getFileName();
            self::assertIsString($file, "{$class} has no file to scan");
            $source = (string) file_get_contents($file);

            foreach (self::BANNED_TOKENS as $token) {
                self::assertStringNotContainsString(
                    $token,
                    $source,
                    "{$class} contains \"{$token}\": the cold path must reach Telegram only through an injected api map",
                );
            }
        }
    }

    public function test_the_resolver_takes_no_constructor_arguments_at_all(): void
    {
        // A resolver that needed a scope, an api map, a session or a config
        // would put a credential back on the cold path. Zero parameters is the
        // structural statement of that, and it is checked rather than assumed.
        $constructor = (new ReflectionClass(TelegramTier::class))->getConstructor();

        self::assertTrue($constructor === null || $constructor->getNumberOfParameters() === 0);
    }
}

/**
 * A stream wrapper that refuses to open anything. Registered for every dial
 * scheme around the cold-path tests so an accidental fopen() is a hard failure
 * rather than an outbound connection this suite never notices.
 */
final class ThrowingDialStream
{
    /** @var resource|null */
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        throw new RuntimeException('the cold path must never dial: refused to open '.$path);
    }

    public function stream_close(): void {}

    public function stream_read(int $count): string
    {
        return '';
    }

    public function stream_write(string $data): int
    {
        return 0;
    }

    public function stream_eof(): bool
    {
        return true;
    }

    public function stream_tell(): int
    {
        return 0;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return false;
    }

    public function stream_stat(): array
    {
        return [];
    }
}
