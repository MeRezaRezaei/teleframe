<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\Restorer;
use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use MeRezaRezaei\Teleframe\Tests\Support\FakeVaultApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * W-26 (senior inspection, security):
 *  (a) TelegramVault manifests are AEAD-sealed on the channel — the
 *      plaintext JSON (paths/salt) never appears as message text, each post
 *      carries a fresh ciphertext header, and the payload still round-trips
 *      through putManifest/getLatestManifest. Chunk captions drop the
 *      plaintext hash (the on-channel FILE NAME must stay the hash: the
 *      prune GC keep-set compares against it).
 *  (b/c) Restorer creates target dirs 0700 / files 0600 and refuses any
 *      symlink between the target and the write destination, plus a
 *      directory standing where a file must land.
 */
final class BackupW26Test extends TestCase
{
    private string $base;

    // vendor/ is a symlink to the main tree: pin this tree's (fixed)
    // TelegramVault/Restorer sources before any test body autoloads them.
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 2).'/tests/Schema/FixesAutoload.php';
    }

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir().'/w26-'.uniqid();
        mkdir($this->base, 0700, true);
    }

    protected function tearDown(): void
    {
        self::rrmdir($this->base);
        parent::tearDown();
    }

    /** @return list<array{call: string, args: list<mixed>}> all recorded $name calls, oldest first */
    private function callsOf(FakeVaultApi $api, string $name): array
    {
        $matches = [];
        foreach ($api->calls as $call) {
            if ($call['call'] === $name) {
                $matches[] = $call;
            }
        }

        return $matches;
    }

    // ---------------------------------------------------------------- (a) vault

    public function test_manifest_posts_never_embed_plaintext_paths_or_salt(): void
    {
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $api->map(), 'correct horse');

        $salt = bin2hex(VaultCrypto::newSalt());
        $manifest = [
            'version' => 1,
            'created' => 1700000000,
            'salt' => $salt,
            'chunk_hashes' => ['a'.str_repeat('0', 63)],
            'files' => [
                ['path' => '/home/me/.ssh/id_rsa', 'chunks' => ['a'.str_repeat('0', 63)], 'size' => 123, 'mode' => 0600],
            ],
        ];
        $json = json_encode($manifest, JSON_THROW_ON_ERROR);

        $vault->putManifest($json);
        $text = $this->callsOf($api, 'sendText')[0]['args'][1];
        self::assertIsString($text);

        self::assertStringStartsWith('TBMANIFEST1:', $text, 'marker prefix is preserved for detection');
        self::assertStringNotContainsString('/home/me/.ssh/id_rsa', $text, 'plaintext paths never reach the channel');
        self::assertStringNotContainsString($salt, $text, 'plaintext salt never reaches the channel');
        self::assertStringNotContainsString($json, $text, 'the raw manifest JSON never reaches the channel');
        self::assertNotSame('TBMANIFEST1:'.base64_encode($json), $text, 'payload is sealed, not base64 json');

        self::assertSame($manifest, $vault->getLatestManifest(), 'sealed manifest round-trips byte-exact');
    }

    public function test_identical_manifest_posts_encode_differently(): void
    {
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $api->map(), 'correct horse');

        $vault->putManifest('{"version":1}');
        $vault->putManifest('{"version":1}');

        $texts = array_map(
            static fn (array $call): string => $call['args'][1],
            $this->callsOf($api, 'sendText'),
        );
        self::assertNotSame($texts[0], $texts[1], 'a fresh AEAD header per run breaks cross-run correlation');

        self::assertSame(['version' => 1], $vault->getLatestManifest());
    }

    public function test_tampered_sealed_manifest_fails_loud(): void
    {
        $api = new FakeVaultApi;
        $channelId = $api->addChannel('teleframe-backup:set1');
        $vault = new TelegramVault('set1', $api->map(), 'correct horse');

        $vault->putManifest('{"version":1}');
        $text = $this->callsOf($api, 'sendText')[0]['args'][1];
        $sealed = base64_decode((string) substr((string) $text, strlen('TBMANIFEST1:')), true);
        self::assertIsString($sealed);
        $sealed[5] = $sealed[5] === "\x00" ? "\x01" : "\x00"; // flip one ciphertext byte
        $api->postText($channelId, 'TBMANIFEST1:'.base64_encode($sealed)); // newest post wins

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        $vault->getLatestManifest();
    }

    public function test_chunk_image_on_channel_keeps_hash_file_name_but_not_hash_caption(): void
    {
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $api->map(), 'correct horse');

        $vault->putChunk('hash-a', 'bytes-a');
        $send = $this->callsOf($api, 'sendDocument')[0];

        self::assertSame('hash-a', $send['args'][1]['name'], 'file name stays the content hash (prune keep-set depends on it)');
        self::assertSame(TelegramVault::CHUNK_CAPTION, $send['args'][2]);
        self::assertStringNotContainsString('hash-a', $send['args'][2], 'caption no longer repeats the plaintext hash');
    }

    // ------------------------------------------------------------ (b/c) restorer

    public function test_restore_creates_0700_directories_and_0600_files(): void
    {
        $target = $this->base.'/target';
        $content = 'credential dump content';
        $vault = new InMemoryVault;
        self::seedVault($vault, 'correct horse', 'sub/deep/file.txt', $content);

        $result = Restorer::restore($vault, 'correct horse', $target);

        self::assertSame(['files' => 1, 'bytes' => strlen($content)], $result);
        self::assertFileExists($target.'/sub/deep/file.txt');
        self::assertSame($content, file_get_contents($target.'/sub/deep/file.txt'));
        self::assertSame(0700, fileperms($target) & 0777, 'created target dir is 0700, not the old 0777');
        self::assertSame(0700, fileperms($target.'/sub') & 0777, 'every created directory is 0700, the first nested one included');
        self::assertSame(0700, fileperms($target.'/sub/deep') & 0777, 'every created directory is 0700, at any depth');
        self::assertSame(0600, fileperms($target.'/sub/deep/file.txt') & 0777, 'restored files are 0600');
    }

    public function test_restore_refuses_a_symlink_inside_the_target(): void
    {
        $target = $this->base.'/target';
        $outside = $this->base.'/outside';
        mkdir($target, 0777, true);
        mkdir($outside, 0777, true);
        self::assertTrue(symlink($outside, $target.'/evil'), 'set up the hostile link');

        $vault = new InMemoryVault;
        self::seedVault($vault, 'correct horse', 'evil/file.txt', 'pwned');

        try {
            Restorer::restore($vault, 'correct horse', $target);
            self::fail('restore must refuse to write through a symlink');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('symlink', $e->getMessage());
        }

        self::assertTrue(is_link($target.'/evil'), 'the planted symlink is not removed');
        self::assertFalse(file_exists($outside.'/file.txt'), 'nothing is written through the symlink');
        self::assertFalse(file_exists($target.'/evil/file.txt'), 'the symlinked path is never descended');
    }

    public function test_restore_refuses_a_symlink_as_the_final_destination(): void
    {
        $target = $this->base.'/target';
        mkdir($target, 0777, true);
        $outside = $this->base.'/outside.txt';
        file_put_contents($outside, 'outside content');
        self::assertTrue(symlink($outside, $target.'/root.txt'), 'plant a symlink where the file must land');

        $vault = new InMemoryVault;
        self::seedVault($vault, 'correct horse', 'root.txt', 'restored content');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('symlink');

        Restorer::restore($vault, 'correct horse', $target);
    }

    public function test_restore_refuses_a_symlinked_parent_before_creating_any_write(): void
    {
        $target = $this->base.'/target';
        $outside = $this->base.'/outside';
        mkdir($target, 0777, true);
        mkdir($outside, 0777, true);
        self::assertTrue(symlink($outside, $target.'/sub'), 'a symlink sits where a parent dir is needed');

        $vault = new InMemoryVault;
        self::seedVault($vault, 'correct horse', 'sub/file.txt', 'pwned');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('symlink');

        Restorer::restore($vault, 'correct horse', $target);
    }

    public function test_restore_refuses_a_directory_standing_where_the_file_would_land(): void
    {
        $target = $this->base.'/target';
        mkdir($target.'/conf', 0777, true);

        $vault = new InMemoryVault;
        self::seedVault($vault, 'correct horse', 'conf', 'must not replace a directory');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('destination is a directory');

        Restorer::restore($vault, 'correct horse', $target);
    }

    public function test_restore_still_overwrites_a_pre_existing_regular_file(): void
    {
        $target = $this->base.'/target';
        mkdir($target, 0777, true);
        file_put_contents($target.'/root.txt', 'old content');

        $vault = new InMemoryVault;
        self::seedVault($vault, 'correct horse', 'root.txt', 'new content');

        Restorer::restore($vault, 'correct horse', $target);

        self::assertSame('new content', file_get_contents($target.'/root.txt'));
        self::assertSame(0600, fileperms($target.'/root.txt') & 0777);
    }

    private static function seedVault(InMemoryVault $vault, string $passphrase, string $path, string $content): void
    {
        $salt = VaultCrypto::newSalt();
        $key = VaultCrypto::deriveKey($passphrase, $salt);
        $hash = hash('sha256', $content);
        $vault->putChunk($hash, VaultCrypto::encryptChunk($key, $content));

        $vault->putManifest(json_encode([
            'version' => 1,
            'created' => time(),
            'salt' => bin2hex($salt),
            'chunk_hashes' => [$hash],
            'files' => [
                ['path' => $path, 'chunks' => [$hash], 'size' => strlen($content), 'mode' => 0644],
            ],
        ], JSON_THROW_ON_ERROR));
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
            if (is_link($path) || is_file($path)) {
                unlink($path);
            } else {
                self::rrmdir($path);
            }
        }
        rmdir($dir);
    }
}
