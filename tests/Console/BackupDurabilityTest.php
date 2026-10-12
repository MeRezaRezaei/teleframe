<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * The two defects a readiness audit found in "Telegram as permanent backup
 * storage":
 *
 *  1. the shipped default driver was `memory` — a PROCESS-LOCAL vault — so a
 *     default install reported a cheerful success while storing nothing;
 *  2. the manifest key came from `teleframe.tstore.passphrase` while the
 *     chunks were sealed with `TELEFRAME_BACKUP_PASSPHRASE`, so `run` could
 *     not read back the index it had just written.
 *
 * Both are asserted here against the real command and the real provider
 * factory (only the Telegram wire is faked, by DurableChannelScope).
 */
final class BackupDurabilityTest extends TestCase
{
    private const PASSPHRASE = 'one secret for one vault';

    private string $source;

    private ?DurableChannelScope $scope = null;

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $this->source = sys_get_temp_dir().'/tf-durable-src-'.uniqid();
        mkdir($this->source.'/sub', 0777, true);
        file_put_contents($this->source.'/a.txt', 'durable hello world');
        file_put_contents($this->source.'/sub/c.txt', 'a different payload entirely');
    }

    protected function tearDown(): void
    {
        self::rrmdir($this->source);
        $this->scope?->forget();

        parent::tearDown();
    }

    /**
     * The shipped default: whatever `teleframe.backup.driver` resolves to with
     * no host configuration at all must be a DURABLE store. The test asserts
     * the shipped config file's own default, so the value cannot silently
     * regress back to the process-local vault.
     */
    public function test_shipped_default_driver_is_durable(): void
    {
        putenv('TELEFRAME_BACKUP_DRIVER');
        unset($_ENV['TELEFRAME_BACKUP_DRIVER'], $_SERVER['TELEFRAME_BACKUP_DRIVER']);

        $shipped = require dirname(__DIR__, 2).'/src/Laravel/config/teleframe.php';

        self::assertSame('telegram', $shipped['backup']['driver']);
    }

    /**
     * With the shipped default and nothing else configured, `run` must
     * REFUSE — never exit 0 having stored nothing. An unconfigured telegram
     * driver has no account to write to, and says so.
     */
    public function test_default_install_run_refuses_loudly_instead_of_pretending(): void
    {
        $this->app['config']->set('teleframe.backup', [
            'chunk_size' => 10,
            'sets' => ['default' => ['paths' => [$this->source], 'excludes' => []]],
        ]);

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutputToContain('backup driver "telegram" needs teleframe.backup.account')
            ->assertExitCode(1);
    }

    /**
     * The process-local vault stays available for offline tests, but only as
     * an ACKNOWLEDGED choice: a run against it refuses unless the host opted
     * in by name. This is the "green run that stored nothing" tripwire.
     */
    public function test_volatile_vault_run_refuses_unless_acknowledged(): void
    {
        $this->useMemoryDriver();

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutputToContain('stores NOTHING')
            ->assertExitCode(1);
    }

    public function test_volatile_vault_run_is_allowed_once_acknowledged(): void
    {
        $this->useMemoryDriver();
        $this->app['config']->set('teleframe.backup.allow_volatile_vault', true);

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutputToContain('backup set=default {uploaded:')
            ->assertExitCode(0);
    }

    public function test_empty_paths_is_an_actionable_error(): void
    {
        $this->useMemoryDriver();
        $this->app['config']->set('teleframe.backup.allow_volatile_vault', true);
        $this->app['config']->set('teleframe.backup.sets.default.paths', []);

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutputToContain('teleframe.backup.sets.default.paths')
            ->assertExitCode(1);
    }

    /**
     * One secret for one vault. The chunks and the manifest index that says
     * which chunks exist MUST come from the same key, so the set this run
     * writes is verifiable and restorable with that same secret.
     */
    public function test_a_set_written_now_is_verifiable_and_restorable_with_the_same_secret(): void
    {
        $this->useTelegramDriver();

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutputToContain('backup set=default {uploaded:')
            ->assertExitCode(0);

        // Durably written: the store outlives the vault object, and it holds
        // real bytes — not a count of zero.
        self::assertGreaterThan(0, $this->scope()->storedBytes(), 'nothing reached the durable store');

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('status set=default {files:2, bytes:')
            ->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'verify', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutputToContain('missing:0, corrupt:0')
            ->assertExitCode(0);

        $target = sys_get_temp_dir().'/tf-durable-dst-'.uniqid();
        $this->artisan('teleframe:backup', [
            'action' => 'restore',
            '--passphrase-file' => $this->passphraseFile(),
            '--target' => $target,
        ])->assertExitCode(0);

        self::assertSame(
            hash_file('sha256', $this->source.'/sub/c.txt'),
            hash_file('sha256', $target.'/sub/c.txt'),
            'the restored file must be byte-identical to the source',
        );
        self::rrmdir($target);
    }

    /**
     * The seam bug itself: the manifest key used to come from the TStore
     * passphrase while the chunks used TELEFRAME_BACKUP_PASSPHRASE. With both
     * set to different values the vault is refused AT BIND TIME, naming both
     * keys — never a run that writes chunks under one secret and cannot read
     * its own index under another.
     */
    public function test_a_prompt_only_passphrase_is_refused_as_unreadable_later(): void
    {
        $this->useTelegramDriver();
        $this->app['config']->set('teleframe.backup.passphrase', '');
        $this->app['config']->set('teleframe.tstore.passphrase', '');

        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])
            ->expectsOutputToContain('needs the vault passphrase in CONFIG')
            ->assertExitCode(1);
    }

    public function test_mismatched_passphrase_keys_are_refused_naming_both(): void
    {
        $this->useTelegramDriver();
        $this->app['config']->set('teleframe.tstore.passphrase', 'a different secret');

        // Refused AT BIND TIME: resolving the factory itself throws, before
        // any vault (and therefore any upload) exists.
        try {
            $this->app->make(BackupCommand::VAULT_FACTORY_KEY);
            self::fail('the vault factory must refuse a two-key passphrase mismatch');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('teleframe.backup.passphrase', $e->getMessage());
            self::assertStringContainsString('TELEFRAME_BACKUP_PASSPHRASE', $e->getMessage());
            self::assertStringContainsString('teleframe.tstore.passphrase', $e->getMessage());
            self::assertStringContainsString('TELEFRAME_TSTORE_PASSPHRASE', $e->getMessage());
        }

        // And through the command, so a host sees it on the CLI too.
        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('teleframe.backup.passphrase')
            ->assertExitCode(1);
    }

    public function test_matching_tstore_passphrase_is_accepted_as_the_legacy_alias(): void
    {
        $this->useTelegramDriver();
        // Only the legacy key set: it IS the same secret, so the vault still
        // gets one key and the round trip works.
        $this->app['config']->set('teleframe.backup.passphrase', '');
        $this->app['config']->set('teleframe.tstore.passphrase', self::PASSPHRASE);
        $this->app['config']->set('teleframe.backup.sets.default.paths', [$this->source]);

        \Illuminate\Support\Facades\Artisan::call('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()]);
        $this->artisan('teleframe:backup', ['action' => 'run', '--passphrase-file' => $this->passphraseFile()])->assertExitCode(0);

        $this->artisan('teleframe:backup', ['action' => 'status'])
            ->expectsOutputToContain('status set=default {files:2')
            ->assertExitCode(0);
    }

    // ------------------------------------------------------------------
    // wiring
    // ------------------------------------------------------------------

    private function useMemoryDriver(): void
    {
        $this->app['config']->set('teleframe.backup', [
            'driver' => 'memory',
            'chunk_size' => 10,
            'sets' => ['default' => ['paths' => [$this->source], 'excludes' => []]],
        ]);
    }

    private function useTelegramDriver(): void
    {
        $this->app['config']->set('teleframe.backup', [
            'driver' => 'telegram',
            'account' => 7,
            'chunk_size' => 10,
            // ONE key: the same secret the command reads from the
            // environment/file, so chunks and index are sealed alike.
            'passphrase' => self::PASSPHRASE,
            'sets' => ['default' => ['paths' => [$this->source], 'excludes' => []]],
        ]);
        $this->app['config']->set('teleframe.tstore.passphrase', '');

        // The seam binds a FACTORY returning callable(int): UserAccountScope.
        $this->app->bind(BackfillCommand::SCOPE_RESOLVER_KEY, fn (): callable => fn (int $accountId): UserAccountScope => $this->scope());
    }

    private function scope(): DurableChannelScope
    {
        if ($this->scope === null) {
            $this->scope = new DurableChannelScope(sys_get_temp_dir().'/tf-durable-channel-'.uniqid().'.json');
        }

        return $this->scope;
    }

    private function passphraseFile(): string
    {
        $path = sys_get_temp_dir().'/tf-durable-pass-'.uniqid();
        file_put_contents($path, self::PASSPHRASE);

        return $path;
    }

    private static function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            is_dir($path) ? self::rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}

/**
 * A UserAccountScope that emulates ONE Telegram channel over a JSON file, so
 * the backup vault's real call map (scopeApi) is exercised end to end without
 * a socket — and the store is DURABLE across vault objects, which is what
 * makes "did anything actually persist?" a real assertion.
 */
final class DurableChannelScope extends UserAccountScope
{
    private const PART_LIMIT = 524288;

    private const CHANNEL_ID = 1000;

    private const ACCESS_HASH = 4242;

    /** @var array{channel: ?array<string, int|string>, messages: list<array<string, mixed>>, docs: array<string, string>, parts: array<string, array<int, string>>} */
    private array $state;

    public function __construct(private readonly string $file)
    {
        $session = new SessionData(dcId: 2, authKey: 'k', userId: 42);

        parent::__construct(new MTProtoClient(apiId: 1, apiHash: 'x', session: $session), $session);

        $this->load();
    }

    /** Stable short key for a 64-bit Telegram file_id. */
    private function partKey(string $fileId): string
    {
        return 'f'.substr(hash('sha256', $fileId), 0, 12);
    }

    /** Total bytes of document payloads held by the emulated channel. */
    public function storedBytes(): int
    {
        $this->load();

        $bytes = 0;
        foreach ($this->state['docs'] as $payload) {
            $bytes += strlen($payload);
        }

        return $bytes;
    }

    public function raw(): string
    {
        return (string) @file_get_contents($this->file);
    }

    public function forget(): void
    {
        @unlink($this->file);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params = []): array
    {
        $this->load();

        $result = match ($method) {
            'messages.getDialogs' => $this->dialogs(),
            'channels.createChannel' => $this->createChannelCall((string) ($params['title'] ?? 'untitled')),
            'upload.saveFilePart', 'upload.saveBigFilePart' => $this->saveFilePartCall($params),
            'messages.sendMedia' => $this->sendMediaCall($params),
            'messages.sendMessage' => $this->sendMessageCall($params),
            'messages.search' => $this->rows($this->matchingMessages((string) ($params['q'] ?? ''))),
            'messages.getHistory' => $this->rows($this->historyMessages((int) ($params['offset_id'] ?? 0))),
            'messages.deleteMessages' => $this->deleteMessagesCall((array) ($params['id'] ?? [])),
            'upload.getFile' => $this->getFileCall($params),
            default => throw new \RuntimeException("DurableChannelScope cannot serve {$method}"),
        };

        $this->save();

        return $result;
    }

    /** @return array<string, mixed> */
    private function dialogs(): array
    {
        $channel = $this->state['channel'];

        return $channel === null ? ['chats' => []] : ['chats' => [$this->chatRow()]];
    }

    /**
     * @return array<string, mixed>
     */
    private function createChannelCall(string $title): array
    {
        $this->state['channel'] = ['id' => self::CHANNEL_ID, 'title' => $title, 'access_hash' => self::ACCESS_HASH];

        return ['chats' => [$this->chatRow()]];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function saveFilePartCall(array $params): array
    {
        $fileId = (string) ($params['file_id'] ?? '0');
        // Keyed by a SHORT surrogate: a Telegram file_id is a 64-bit int that
        // JSON cannot round-trip through an array key without precision loss.
        $this->state['parts'][$this->partKey($fileId)][(int) ($params['file_part'] ?? 0)] = (string) ($params['bytes'] ?? '');

        return ['ok' => true];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function sendMediaCall(array $params): array
    {
        $media = is_array($params['media'] ?? null) ? $params['media'] : [];
        $file = is_array($media['file'] ?? null) ? $media['file'] : [];
        $fileId = (string) ($file['id'] ?? '0');
        $name = $this->documentName($media);

        $parts = $this->state['parts'][$this->partKey($fileId)] ?? [];
        ksort($parts);
        $bytes = '';
        foreach ($parts as $part) {
            $bytes .= $part;
        }
        unset($this->state['parts'][$this->partKey($fileId)]);

        $docId = count($this->state['docs']) + 1;
        $this->state['docs'][(string) $docId] = $bytes;
        $message = $this->appendMessage(['media' => ['document' => [
            '_' => 'document',
            'id' => $docId,
            'access_hash' => 9,
            'file_reference' => '',
            'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => $name]],
        ]], 'message' => '']);

        return ['messages' => [$message]];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function sendMessageCall(array $params): array
    {
        $message = $this->appendMessage(['media' => null, 'message' => (string) ($params['message'] ?? '')]);

        return ['messages' => [$message]];
    }

    /**
     * The text index answers LOOSELY (substring), like the real one; the
     * vault's own string-function prefix/exact checks are what must hold.
     *
     * @return list<array<string, mixed>>
     */
    private function matchingMessages(string $query): array
    {
        $matches = [];
        foreach ($this->state['messages'] as $message) {
            if ($query === '' || str_contains($this->rowName($message), $query)) {
                $matches[] = $message;
            }
        }

        return array_reverse($matches);
    }

    /** Newest first, strictly below $offsetId (0 = newest). @return list<array<string, mixed>> */
    private function historyMessages(int $offsetId): array
    {
        $rows = array_reverse($this->state['messages']);
        if ($offsetId === 0) {
            return $rows;
        }

        return array_values(array_filter($rows, static fn (array $row): bool => (int) $row['id'] < $offsetId));
    }

    /**
     * @param  list<mixed>  $ids
     * @return array<string, mixed>
     */
    private function deleteMessagesCall(array $ids): array
    {
        $drop = [];
        foreach ($ids as $id) {
            $drop[(int) $id] = true;
        }

        $this->state['messages'] = array_values(array_filter(
            $this->state['messages'],
            static fn (array $row): bool => ! isset($drop[(int) $row['id']]),
        ));

        return ['ok' => true];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function getFileCall(array $params): array
    {
        $id = (string) ($params['location']['id'] ?? '');
        $offset = max(0, (int) ($params['offset'] ?? 0));
        $bytes = $this->state['docs'][$id] ?? '';

        return ['bytes' => substr($bytes, $offset, self::PART_LIMIT)];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function appendMessage(array $row): array
    {
        $row['id'] = count($this->state['messages']) + 1;
        $this->state['messages'][] = $row;

        return $row;
    }

    /** @return array<string, mixed> */
    private function chatRow(): array
    {
        $channel = $this->state['channel'] ?? [];

        return [
            '_' => 'channel',
            'id' => (int) ($channel['id'] ?? self::CHANNEL_ID),
            'title' => (string) ($channel['title'] ?? ''),
            'access_hash' => (int) ($channel['access_hash'] ?? self::ACCESS_HASH),
        ];
    }

    /**
     * @param  array<string, mixed>  $media
     */
    private function documentName(array $media): string
    {
        $attributes = is_array($media['attributes'] ?? null) ? $media['attributes'] : [];
        foreach ($attributes as $attribute) {
            if (is_array($attribute) && ($attribute['_'] ?? '') === 'documentAttributeFilename') {
                return (string) ($attribute['file_name'] ?? '');
            }
        }

        return '';
    }

    /** @param array<string, mixed> $message */
    private function rowName(array $message): string
    {
        $document = is_array($message['media'] ?? null) ? ($message['media']['document'] ?? null) : null;
        if (is_array($document)) {
            $attributes = is_array($document['attributes'] ?? null) ? $document['attributes'] : [];
            foreach ($attributes as $attribute) {
                if (is_array($attribute) && ($attribute['_'] ?? '') === 'documentAttributeFilename') {
                    return (string) ($attribute['file_name'] ?? '');
                }
            }
        }

        return (string) ($message['message'] ?? '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function rows(array $rows): array
    {
        return ['messages' => $rows];
    }

    /**
     * Binary payloads (encrypted chunks) go through base64 on the way to
     * disk: raw bytes are not valid UTF-8, so json_encode would fail and the
     * store would silently truncate to nothing.
     */
    private function load(): void
    {
        $raw = @file_get_contents($this->file);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        $this->state = is_array($decoded) ? $decoded : [
            'channel' => null,
            'messages' => [],
            'docs' => [],
            'parts' => [],
        ];

        $this->state['docs'] = $this->decodeBlobs((array) ($this->state['docs'] ?? []));
        $this->state['parts'] = array_map(
            fn (array $parts): array => $this->decodeBlobs($parts),
            (array) ($this->state['parts'] ?? []),
        );
    }

    private function save(): void
    {
        $state = $this->state;
        $state['docs'] = $this->encodeBlobs($state['docs']);
        $state['parts'] = array_map(fn (array $parts): array => $this->encodeBlobs($parts), $state['parts']);

        file_put_contents($this->file, (string) json_encode($state));
    }

    /**
     * @param  array<int|string, string>  $blobs
     * @return array<int|string, string>
     */
    private function encodeBlobs(array $blobs): array
    {
        return array_map(static fn (string $bytes): string => base64_encode($bytes), $blobs);
    }

    /**
     * @param  array<int|string, string>  $blobs
     * @return array<int|string, string>
     */
    private function decodeBlobs(array $blobs): array
    {
        return array_map(static fn (string $b64): string => (string) base64_decode($b64, true), $blobs);
    }
}
