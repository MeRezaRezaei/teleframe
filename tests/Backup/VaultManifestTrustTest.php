<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use MeRezaRezaei\Teleframe\Tests\Support\FakeVaultApi;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Manifest trust in the backup vault (findings H5 + manifest rollback).
 *
 * The manifest of manifests IS the index: the file tree, the per-file
 * chunk order, the ordered per-file chunk-hash list and the Argon2id KDF
 * salt. Before the fix its AEAD key was
 * `sodium_crypto_generichash("teleframe-backup:vault-manifest:v1:" .
 * $channelAccessHash)` — an UNKEYED public hash, because the key argument
 * was defaulted to "". Telegram hands that access_hash to every account
 * that can SEE the channel, so the key protecting the manifest was
 * computable by its own audience, and an unresolved access hash (0) made
 * it a hardcoded, globally constant key.
 *
 * These tests pin the corrected model: the key comes from the PASSPHRASE
 * (which the host holds and the channel audience does not), a wrong
 * passphrase does not decrypt, an absent passphrase throws instead of
 * degrading, access_hash 0 throws, and a rollback to an older generation
 * loses to the newer manifest regardless of which was posted last.
 */
final class VaultManifestTrustTest extends TestCase
{
    private const PASSPHRASE = 'correct horse battery staple';

    private const OTHER_PASSPHRASE = 'a completely different passphrase';

    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 2).'/tests/Schema/FixesAutoload.php';
    }

    /** @return array<string, callable> */
    private function map(FakeVaultApi $api): array
    {
        return $api->map();
    }

    // ── the key is the passphrase, not a value the channel hands out ───

    public function test_a_manifest_sealed_under_one_passphrase_does_not_decrypt_under_another(): void
    {
        $api = new FakeVaultApi;
        $writer = new TelegramVault('set1', $this->map($api), self::PASSPHRASE);
        $writer->putManifest('{"version":1,"generation":1}');

        $reader = new TelegramVault('set1', $this->map($api), self::OTHER_PASSPHRASE);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        $reader->getLatestManifest();
    }

    public function test_a_manifest_sealed_under_one_set_passphrase_does_not_decrypt_in_another_set(): void
    {
        $api = new FakeVaultApi;
        $channelB = $api->addChannel('teleframe-backup:set-b');
        $api->addChannel('teleframe-backup:set-a');
        // Both channels report the same access hash (every fake channel
        // does): the set id is mixed into the KDF, so the two manifest keys
        // must differ anyway.
        $writer = new TelegramVault('set-a', $this->map($api), self::PASSPHRASE);
        $writer->putManifest('{"version":1,"generation":1}');

        // A set-b-keyed reader pointed at set-a's manifest text: the same
        // bytes, the same access hash, a different key.
        $text = null;
        foreach ($api->calls as $call) {
            if ($call['call'] === 'sendText') {
                $text = (string) $call['args'][1];
            }
        }
        self::assertIsString($text);
        $api->postText($channelB, $text);

        $other = new TelegramVault('set-b', $this->map($api), self::PASSPHRASE);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');
        $other->getLatestManifest();
    }

    public function test_the_manifest_key_is_not_derivable_from_the_access_hash(): void
    {
        $api = new FakeVaultApi;
        $vault = new TelegramVault('set1', $this->map($api), self::PASSPHRASE);
        $vault->putManifest('{"version":1,"generation":1,"paths":["/home/me/.ssh/id_rsa"]}');

        $text = null;
        foreach ($api->calls as $call) {
            if ($call['call'] === 'sendText') {
                $text = (string) $call['args'][1];
            }
        }
        self::assertIsString($text);
        self::assertStringStartsWith(TelegramVault::MANIFEST_MARKER, $text);

        // The H5 key, spelled out: a KEYLESS BLAKE2b over a value Telegram
        // hands to every account that can see the channel. It must not open
        // the manifest — that is the whole finding, asserted directly.
        $publicKey = sodium_crypto_generichash('teleframe-backup:vault-manifest:v1:4242');
        $sealed = base64_decode(substr($text, strlen(TelegramVault::MANIFEST_MARKER)), true);
        self::assertIsString($sealed);

        $stolen = null;
        try {
            $stolen = VaultCrypto::decryptChunk($publicKey, $sealed);
        } catch (RuntimeException) {
            // refused, as it must be
        }
        self::assertNull(
            $stolen,
            'the manifest opened with a key every member of the channel can compute — the H5 leak, reproduced',
        );

        // …and it is not the real key either.
        self::assertNotSame(
            $publicKey,
            VaultCrypto::deriveManifestKey('set1', self::PASSPHRASE),
            'the manifest key must not be a public hash of the access hash',
        );
    }

    public function test_a_vault_with_no_passphrase_cannot_write_a_manifest(): void
    {
        $vault = new TelegramVault('set1', $this->map(new FakeVaultApi));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no manifest passphrase');

        $vault->putManifest('{"version":1}');
    }

    public function test_a_vault_with_no_passphrase_cannot_read_a_manifest(): void
    {
        $api = new FakeVaultApi;
        (new TelegramVault('set1', $this->map($api), self::PASSPHRASE))->putManifest('{"version":1,"generation":1}');

        $keyless = new TelegramVault('set1', $this->map($api));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no manifest passphrase');

        $keyless->getLatestManifest();
    }

    public function test_an_empty_passphrase_is_refused_by_the_derivation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('empty passphrase is not a key');

        VaultCrypto::deriveManifestKey('set1', '');
    }

    public function test_the_manifest_key_is_per_set_and_per_passphrase(): void
    {
        $keys = [
            VaultCrypto::deriveManifestKey('a', 'p'),
            VaultCrypto::deriveManifestKey('b', 'p'),
            VaultCrypto::deriveManifestKey('a', 'q'),
        ];

        self::assertCount(3, array_unique($keys), 'set id and passphrase both separate the manifest key');
        foreach ($keys as $key) {
            self::assertSame(32, strlen($key));
        }
    }

    // ── channelAccessHash 0 is an error, not a weaker key ──────────────

    public function test_a_channel_with_no_access_hash_is_refused(): void
    {
        $api = new FakeVaultApi;
        $channelId = $api->addChannel('teleframe-backup:set1');
        // A channel row whose access_hash never resolved.
        $api->addChannelWithoutAccessHash($channelId);

        $vault = new TelegramVault('set1', $this->map($api), self::PASSPHRASE);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NO access_hash');

        $vault->inputPeer();
    }

    public function test_a_channel_with_no_access_hash_yields_no_manifest_key_at_all(): void
    {
        $api = new FakeVaultApi;
        $channelId = $api->addChannel('teleframe-backup:set1');
        $api->addChannelWithoutAccessHash($channelId);

        $vault = new TelegramVault('set1', $this->map($api), self::PASSPHRASE);

        try {
            $vault->putManifest('{"version":1}');
            self::fail('an unaddressable channel must not be written to');
        } catch (RuntimeException $e) {
            self::assertStringNotContainsString('tfbk', $e->getMessage());
        }
    }

    // ── rollback ───────────────────────────────────────────────────────

    public function test_a_newer_manifest_with_a_lower_generation_does_not_win(): void
    {
        $api = new FakeVaultApi;
        $channelId = $api->addChannel('teleframe-backup:set1');
        $vault = new TelegramVault('set1', $this->map($api), self::PASSPHRASE);

        $vault->putManifest('{"version":1,"generation":5,"files":["real"]}');
        // A forged/rolled-back EMPTY manifest, posted LAST (so under
        // "highest message id wins" it would be the truth).
        $vault->putManifest('{"version":1,"generation":1,"files":[]}');

        $manifest = $vault->getLatestManifest();

        self::assertSame(5, $manifest['generation']);
        self::assertSame(['real'], $manifest['files'], 'a rollback must not become truth');
    }

    public function test_a_rollback_seen_by_this_instance_is_refused_outright(): void
    {
        $api = new FakeVaultApi;
        $api->addChannel('teleframe-backup:set1');
        $vault = new TelegramVault('set1', $this->map($api), self::PASSPHRASE);

        $vault->putManifest('{"version":1,"generation":9}');
        self::assertSame(9, $vault->getLatestManifest()['generation']);

        // The newer manifest is deleted (or the channel is rolled back) and
        // only the older one is left.
        foreach ($vault->findMessagesByName(TelegramVault::MANIFEST_MARKER) as $entry) {
            $vault->delete((string) $entry['name']);
        }
        $vault->putManifest('{"version":1,"generation":2}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('rollback refused');

        $vault->getLatestManifest();
    }

    public function test_generation_ties_break_on_the_newest_post(): void
    {
        $api = new FakeVaultApi;
        $api->addChannel('teleframe-backup:set1');
        $vault = new TelegramVault('set1', $this->map($api), self::PASSPHRASE);

        $vault->putManifest('{"version":1,"generation":2,"files":["first"]}');
        $vault->putManifest('{"version":1,"generation":2,"files":["second"]}');

        self::assertSame(['second'], $vault->getLatestManifest()['files']);
    }

    public function test_a_legacy_manifest_without_a_generation_still_reads(): void
    {
        $api = new FakeVaultApi;
        $api->addChannel('teleframe-backup:set1');
        $vault = new TelegramVault('set1', $this->map($api), self::PASSPHRASE);

        $vault->putManifest('{"version":1,"files":["legacy"]}');

        self::assertSame(['legacy'], $vault->getLatestManifest()['files']);
    }

    public function test_an_unreadable_newest_manifest_still_fails_loud(): void
    {
        $api = new FakeVaultApi;
        $channelId = $api->addChannel('teleframe-backup:set1');
        $vault = new TelegramVault('set1', $this->map($api), self::PASSPHRASE);
        $vault->putManifest('{"version":1,"generation":1}');
        $api->postText($channelId, 'TBMANIFEST1:'.base64_encode('plain json, never sealed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        $vault->getLatestManifest();
    }
}
