<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Backup\Verifier;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Verifier semantics (plan Phase 4, Task 4): availability sampling —
 * ok means the chunk downloads non-empty; integrity is proven at restore
 * time by secretstream FINAL tags, so plain verify never needs the key.
 * verifyWithKey upgrades the check to full decrypt + plaintext sha256.
 * Sampling rng is injectable: every test drives the exact sample.
 */
final class VerifierTest extends TestCase
{
    private const PASSPHRASE = 'correct horse battery staple';

    private const SALT = "\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff\x00";

    private static ?string $cachedKey = null;

    private static function key(): string
    {
        if (self::$cachedKey === null) {
            self::$cachedKey = VaultCrypto::deriveKey(self::PASSPHRASE, self::SALT);
        }

        return self::$cachedKey;
    }

    /** Identity rng: "randomly" returns every hash — the all-chunks path. */
    private static function allRng(): callable
    {
        return static fn (array $hashes, int $n): array => $hashes;
    }

    public function test_verify_all_ok_on_identity_rng(): void
    {
        $set = self::populatedVault(4);

        $result = Verifier::verify($set->vault, 2, self::allRng()); // n < count: rng decides, here all

        self::assertSame(['checked' => 4, 'ok' => 4, 'missing' => 0], $result);
    }

    public function test_verify_samples_only_what_the_rng_returns(): void
    {
        $set = self::populatedVault(6);

        $seenN = 0;
        $rng = static function (array $hashes, int $n) use (&$seenN): array {
            $seenN = $n;

            return array_slice($hashes, 0, 2); // deterministic pick
        };

        $result = Verifier::verify($set->vault, 2, $rng);

        self::assertSame(2, $seenN);
        self::assertSame(['checked' => 2, 'ok' => 2, 'missing' => 0], $result);
    }

    public function test_verify_covers_all_chunks_when_fewer_than_n(): void
    {
        $set = self::populatedVault(3);

        $result = Verifier::verify($set->vault, 10, self::allRng()); // rng sees n = 3

        self::assertSame(['checked' => 3, 'ok' => 3, 'missing' => 0], $result);
    }

    public function test_verify_default_rng_scales_with_n(): void
    {
        $set = self::populatedVault(4);

        $all = Verifier::verify($set->vault, 4); // default shuffle+slice, n == count -> all
        $one = Verifier::verify($set->vault, 1);

        self::assertSame(['checked' => 4, 'ok' => 4, 'missing' => 0], $all);
        self::assertSame(['checked' => 1, 'ok' => 1, 'missing' => 0], $one);
    }

    public function test_verify_counts_missing_chunks(): void
    {
        $set = self::populatedVault(3);
        $vault = SelectiveVault::missing($set->vault, [$set->hashes[1]]);

        $result = Verifier::verify($vault, 3, self::allRng());

        self::assertSame(['checked' => 3, 'ok' => 2, 'missing' => 1], $result);
    }

    public function test_verify_empty_chunk_is_checked_but_not_ok(): void
    {
        $set = self::populatedVault(2);
        $vault = SelectiveVault::empty($set->vault, [$set->hashes[0]]);

        $result = Verifier::verify($vault, 2, self::allRng());

        // present (not missing) but unusable: ok + missing < checked flags it
        self::assertSame(['checked' => 2, 'ok' => 1, 'missing' => 0], $result);
    }

    public function test_verify_without_manifest_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no manifest');

        Verifier::verify(new InMemoryVault(), 5, self::allRng());
    }

    public function test_verify_rejects_sample_below_one(): void
    {
        $set = self::populatedVault(1);

        $this->expectException(\InvalidArgumentException::class);

        Verifier::verify($set->vault, 0, self::allRng());
    }

    public function test_verify_with_empty_chunk_list_reports_zeroes(): void
    {
        $vault = self::manifestOnlyVault([]);

        $result = Verifier::verify($vault, 5, self::allRng());

        self::assertSame(['checked' => 0, 'ok' => 0, 'missing' => 0], $result);
    }

    public function test_verify_with_key_full_decrypt_and_hash_all_ok(): void
    {
        $set = self::populatedVault(3);

        $result = Verifier::verifyWithKey($set->vault, self::PASSPHRASE, 3, self::allRng());

        self::assertSame(['checked' => 3, 'ok' => 3, 'missing' => 0, 'corrupt' => 0], $result);
    }

    public function test_verify_with_key_counts_missing(): void
    {
        $set = self::populatedVault(2);
        $vault = SelectiveVault::missing($set->vault, [$set->hashes[0]]);

        $result = Verifier::verifyWithKey($vault, self::PASSPHRASE, 2, self::allRng());

        self::assertSame(['checked' => 2, 'ok' => 1, 'missing' => 1, 'corrupt' => 0], $result);
    }

    public function test_verify_with_key_wrong_passphrase_is_all_corrupt(): void
    {
        $set = self::populatedVault(2);

        $result = Verifier::verifyWithKey($set->vault, 'wrong passphrase', 2, self::allRng());

        self::assertSame(['checked' => 2, 'ok' => 0, 'missing' => 0, 'corrupt' => 2], $result);
    }

    public function test_verify_with_key_hash_mismatch_is_corrupt(): void
    {
        // chunk stored under hash A actually decrypts to a different plaintext
        $decoy = VaultCrypto::encryptChunk(self::key(), 'totally other content');

        $vault = new InMemoryVault();
        $vault->putChunk(hash('sha256', 'a'), $decoy);
        $vault->putManifest((string) json_encode([
            'version' => 1,
            'salt' => bin2hex(self::SALT),
            'chunk_hashes' => [hash('sha256', 'a')],
        ]));

        $result = Verifier::verifyWithKey($vault, self::PASSPHRASE, 1, self::allRng());

        self::assertSame(['checked' => 1, 'ok' => 0, 'missing' => 0, 'corrupt' => 1], $result);
    }

    public function test_verify_with_key_rejects_sample_below_one(): void
    {
        $set = self::populatedVault(1);

        $this->expectException(\InvalidArgumentException::class);

        Verifier::verifyWithKey($set->vault, self::PASSPHRASE, 0, self::allRng());
    }

    public function test_verify_with_key_requires_manifest_salt(): void
    {
        $vault = new InMemoryVault();
        $vault->putChunk(hash('sha256', 'a'), 'irrelevant');
        $vault->putManifest((string) json_encode(['version' => 1, 'chunk_hashes' => [hash('sha256', 'a')]]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('salt');

        Verifier::verifyWithKey($vault, self::PASSPHRASE, 1, self::allRng());
    }

    /**
     * Real-encryption fixture: N distinct chunks under one salt, manifest
     * referencing all of them.
     *
     * @return object{vault: InMemoryVault, hashes: list<string>}
     */
    private static function populatedVault(int $count): object
    {
        $vault = self::manifestOnlyVault([]);
        $hashes = [];

        for ($i = 0; $i < $count; $i++) {
            $plaintext = "chunk payload #{$i} " . str_repeat('x', 64 + $i);
            $hash = hash('sha256', $plaintext);
            $vault->putChunk($hash, VaultCrypto::encryptChunk(self::key(), $plaintext));
            $hashes[] = $hash;
        }

        $vault->putManifest((string) json_encode([
            'version' => 1,
            'salt' => bin2hex(self::SALT),
            'chunk_hashes' => $hashes,
        ]));

        return (object) ['vault' => $vault, 'hashes' => $hashes];
    }

    /**
     * @param list<string> $chunkHashes
     */
    private static function manifestOnlyVault(array $chunkHashes): InMemoryVault
    {
        $vault = new InMemoryVault();
        $vault->putManifest((string) json_encode([
            'version' => 1,
            'salt' => bin2hex(self::SALT),
            'chunk_hashes' => $chunkHashes,
        ]));

        return $vault;
    }
}

/**
 * Vault double that hides chosen hashes (getChunk throws) or empties them
 * (getChunk returns '') while delegating everything else to the inner vault.
 */
final class SelectiveVault implements VaultInterface
{
    /** @param list<string> $missing @param list<string> $empty */
    private function __construct(
        private readonly VaultInterface $inner,
        private readonly array $missing,
        private readonly array $empty,
    ) {
    }

    public static function missing(VaultInterface $inner, array $hashes): self
    {
        return new self($inner, $hashes, []);
    }

    public static function empty(VaultInterface $inner, array $hashes): self
    {
        return new self($inner, [], $hashes);
    }

    public function putChunk(string $hash, string $bytes): string
    {
        return $this->inner->putChunk($hash, $bytes);
    }

    public function getChunk(string $hash): string
    {
        if (in_array($hash, $this->missing, true)) {
            throw new RuntimeException("chunk {$hash} not stored in this vault");
        }
        if (in_array($hash, $this->empty, true)) {
            return '';
        }

        return $this->inner->getChunk($hash);
    }

    public function putManifest(string $json): string
    {
        return $this->inner->putManifest($json);
    }

    public function getLatestManifest(): ?array
    {
        return $this->inner->getLatestManifest();
    }

    public function findMessagesByName(string $namePrefix): array
    {
        return $this->inner->findMessagesByName($namePrefix);
    }

    public function delete(string $name): void
    {
        $this->inner->delete($name);
    }
}
