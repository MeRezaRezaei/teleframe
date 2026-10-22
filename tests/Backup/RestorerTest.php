<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\Restorer;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Backup\Verifier;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestorerTest extends TestCase
{
    public function test_restore_rebuilds_byte_identical_files_including_nested_and_empty(): void
    {
        $root = sys_get_temp_dir().'/p4-restore-src-'.uniqid();
        mkdir($root.'/sub/deep', 0777, true);
        $files = [
            '/a.txt' => 'first file',
            '/sub/binary.bin' => random_bytes(1000),
            '/sub/deep/nested.txt' => 'nested content',
            '/empty.txt' => '',
        ];
        $expected = [];
        foreach ($files as $relative => $content) {
            file_put_contents($root.$relative, $content);
            $expected[ltrim($relative, '/')] = $content;
        }

        $vault = new InMemoryVault;
        $stats = BackupRunner::run(['paths' => [$root], 'set' => 'docs', 'chunk_size' => 64], $vault, 'correct horse');
        self::assertSame(count($files), $stats['files']);

        $target = sys_get_temp_dir().'/p4-restore-dst-'.uniqid();
        $restored = Restorer::restore($vault, 'correct horse', $target);

        $expectedBytes = array_sum(array_map('strlen', $expected));
        self::assertSame(['files' => count($files), 'bytes' => $expectedBytes], $restored);

        foreach ($expected as $relative => $content) {
            $restoredPath = $target.'/'.$relative;
            self::assertFileExists($restoredPath, "{$relative} must be restored, nested paths included");
            self::assertSame(hash('sha256', $content), hash_file('sha256', $restoredPath), $relative);
        }
    }

    public function test_restore_without_manifest_fails_loud(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no manifest');

        Restorer::restore(new InMemoryVault, 'pw', sys_get_temp_dir().'/p4-restore-none-'.uniqid());
    }

    public function test_wrong_passphrase_fails_with_decryption_error(): void
    {
        $root = self::singleFileSet('secret payload');

        $vault = new InMemoryVault;
        BackupRunner::run(['paths' => [$root]], $vault, 'right passphrase');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        Restorer::restore($vault, 'wrong passphrase', sys_get_temp_dir().'/p4-restore-wrong-'.uniqid());
    }

    public function test_the_corrupter_always_actually_corrupts(): void
    {
        // The tamper test above is only evidence if the tampering HAPPENED, and
        // its corrupter used to be a no-op 1 time in 256. A test that cannot
        // fail because its own fixture was never broken is not a test, so this
        // pins the corrupter directly, on the exact input that used to slip
        // through: a chunk whose first byte is ALREADY 0xff.
        $inner = new InMemoryVault;
        $vault = new CorruptingVault($inner);

        foreach (["\xff", "\xff\x00\x01\x02", "\x00", "\x7f", "\xfe"] as $index => $bytes) {
            // putChunk() returns a REFERENCE, not the bytes, so the assertion
            // has to read the bytes back — comparing against its return value
            // would pass unconditionally, which is how this guard shipped
            // vacuous the first time.
            $hash = 'probe-'.$index;
            $vault->putChunk($hash, $bytes);
            $stored = $inner->getChunk($hash);

            self::assertNotSame(
                $bytes,
                $stored,
                'the corrupter stored its input unchanged for '.bin2hex($bytes)
                .' — a tamper test over an uncorrupted fixture proves nothing',
            );
        }
    }

    public function test_tampered_chunk_fails_decryption(): void
    {
        $root = self::singleFileSet('secret payload');

        $inner = new InMemoryVault;
        $vault = new CorruptingVault($inner);
        BackupRunner::run(['paths' => [$root]], $vault, 'right passphrase');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        Restorer::restore($inner, 'right passphrase', sys_get_temp_dir().'/p4-restore-tamper-'.uniqid());
    }

    // ── DEFECT-1: content verification on the restore path ──────────────

    public function test_a_substituted_chunk_is_refused_even_though_it_decrypts_cleanly(): void
    {
        // The substitution the AEAD cannot see: chunk 1's legitimate
        // ciphertext served at chunk 0's content address. Every byte is
        // authentic and every byte is wrong, so only comparing the DECRYPTED
        // plaintext against the address the manifest asked for can tell.
        [$vault, $hostile] = self::twoChunkSetWithSubstitution();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not match its content address');

        Restorer::restore($hostile, 'pw', sys_get_temp_dir().'/p4-restore-sub-'.uniqid());
    }

    public function test_the_substituting_fixture_really_substitutes(): void
    {
        // Without this, the test above is vacuous on a fixture that silently
        // served the RIGHT bytes and the restore would have thrown for some
        // other reason — or not thrown at all while still proving nothing.
        [$vault, $hostile] = self::twoChunkSetWithSubstitution();

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        $chunks = $manifest['files'][0]['chunks'];
        self::assertCount(2, $chunks, 'the fixture needs two chunks to substitute between');

        $right = $vault->getChunk($chunks[0]);
        $wrong = $hostile->getChunk($chunks[0]);

        self::assertNotSame(
            $right,
            $wrong,
            'the substituting vault served chunk 0 honestly — a substitution test over an honest fixture proves nothing',
        );
        self::assertSame(
            $vault->getChunk($chunks[1]),
            $wrong,
            'chunk 0\'s address must serve chunk 1\'s ciphertext',
        );
    }

    public function test_a_substituted_chunk_is_what_the_verifier_calls_corrupt(): void
    {
        // Pins the asymmetry this fix closes: the verifier has ALWAYS caught
        // this substitution, restore has not. If the verifier ever stops
        // catching it, this test says so rather than leaving the expectation
        // resting on a comment.
        [$vault, $hostile] = self::twoChunkSetWithSubstitution();

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        $target = $chunks = $manifest['files'][0]['chunks'][0];

        $verdict = Verifier::verifyWithKey($hostile, 'pw', 1, static fn (array $hashes, int $n): array => [$target]);

        self::assertSame(1, $verdict['corrupt'], 'verifyWithKey must score the substituted chunk corrupt');
        self::assertSame(0, $verdict['ok']);
    }

    public function test_restore_refuses_a_chunk_list_that_does_not_add_up_to_the_declared_size(): void
    {
        // BackupRunner records each file's plaintext size in the manifest. A
        // reassembly that disagrees means the chunk list is wrong, and the
        // content-address check cannot see it (every chunk is individually
        // correct) — only the declared total can.
        $root = self::singleFileSet('secret payload');

        $vault = new InMemoryVault;
        BackupRunner::run(['paths' => [$root]], $vault, 'pw');

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        self::assertSame(14, $manifest['files'][0]['size'], 'the manifest must actually declare a size for this test to mean anything');
        $manifest['files'][0]['size'] = 999;
        $vault->putManifest((string) json_encode($manifest, JSON_THROW_ON_ERROR));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('the manifest declares 999');

        Restorer::restore($vault, 'pw', sys_get_temp_dir().'/p4-restore-size-'.uniqid());
    }

    public function test_a_manifest_without_a_size_still_restores(): void
    {
        // A legacy manifest never carried `size`. It must NOT be rejected over
        // a field it never had — the size check is conditional on the field
        // being present and an int.
        $root = self::singleFileSet('secret payload');

        $vault = new InMemoryVault;
        BackupRunner::run(['paths' => [$root]], $vault, 'pw');

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        unset($manifest['files'][0]['size']);
        $vault->putManifest((string) json_encode($manifest, JSON_THROW_ON_ERROR));

        $target = sys_get_temp_dir().'/p4-restore-nosize-'.uniqid();
        $stats = Restorer::restore($vault, 'pw', $target);

        self::assertSame(['files' => 1, 'bytes' => 14], $stats);
        self::assertSame('secret payload', file_get_contents($target.'/only.txt'));
    }

    // ── DEFECT-1b: no partial tree on failure ───────────────────────────

    public function test_a_failure_on_a_later_file_leaves_the_target_untouched(): void
    {
        // Files are fetched one at a time and written as they verify, so a
        // failure on file 3 of 3 used to leave files 1-2 on disk: a
        // plausible-looking partial tree with nothing marking it incomplete.
        [$vault, $hostile] = self::twoFileSetWithSubstitutionOnTheSecondFile();

        $target = sys_get_temp_dir().'/p4-restore-partial-'.uniqid();

        try {
            Restorer::restore($hostile, 'pw', $target);
            self::fail('the substituted chunk must abort the restore');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('does not match its content address', $e->getMessage());
        }

        self::assertFileDoesNotExist(
            $target.'/a.txt',
            'a restore that failed must not leave the files it had already written behind',
        );
        self::assertSame(
            [],
            array_values(array_diff(scandir($target), ['.', '..'])),
            'the target must hold nothing of a failed restore',
        );
    }

    public function test_the_staging_directory_is_cleaned_up_after_a_failed_restore(): void
    {
        [$vault, $hostile] = self::twoFileSetWithSubstitutionOnTheSecondFile();

        $target = sys_get_temp_dir().'/p4-restore-stage-'.uniqid();

        try {
            Restorer::restore($hostile, 'pw', $target);
            self::fail('the substituted chunk must abort the restore');
        } catch (RuntimeException) {
            // expected
        }

        self::assertDirectoryDoesNotExist($target.'/.teleframe-staging');
    }

    public function test_a_manifest_path_inside_the_staging_directory_is_rejected(): void
    {
        // The staging area is the restore's own scratch space. A manifest that
        // names a file inside it would have that file moved out from under the
        // restore writing it.
        $root = self::singleFileSet('innocent');

        $vault = new InMemoryVault;
        BackupRunner::run(['paths' => [$root]], $vault, 'pw');

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        $manifest['files'][0]['path'] = '.teleframe-staging/sneaky.txt';
        $vault->putManifest((string) json_encode($manifest, JSON_THROW_ON_ERROR));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsafe manifest path');

        Restorer::restore($vault, 'pw', sys_get_temp_dir().'/p4-restore-stagepath-'.uniqid());
    }

    public function test_manifest_path_traversal_is_rejected_before_any_write(): void
    {
        $root = self::singleFileSet('innocent');

        $vault = new InMemoryVault;
        BackupRunner::run(['paths' => [$root]], $vault, 'pw');

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        $manifest['files'][0]['path'] = '../pwned.txt';
        $vault->putManifest((string) json_encode($manifest, JSON_THROW_ON_ERROR));

        $base = sys_get_temp_dir().'/p4-restore-travel-'.uniqid();
        $target = $base.'/target';

        try {
            Restorer::restore($vault, 'pw', $target);
            self::fail('traversal manifest must be rejected');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('unsafe manifest path', $e->getMessage());
        }

        self::assertFileDoesNotExist($base.'/pwned.txt');
        self::assertDirectoryDoesNotExist($target);
    }

    private static function singleFileSet(string $content): string
    {
        $root = sys_get_temp_dir().'/p4-restore-one-'.uniqid();
        mkdir($root, 0777, true);
        file_put_contents($root.'/only.txt', $content);

        return $root;
    }

    /**
     * A backed-up set of ONE file split into TWO chunks, plus a vault that
     * serves chunk 1's ciphertext at chunk 0's content address.
     *
     * @return array{0: InMemoryVault, 1: SubstitutingVault}
     */
    private static function twoChunkSetWithSubstitution(): array
    {
        $root = sys_get_temp_dir().'/p4-restore-two-'.uniqid();
        mkdir($root, 0777, true);
        file_put_contents($root.'/only.txt', 'AAAAAAAA'.'BBBBBBBB');

        $vault = new InMemoryVault;
        BackupRunner::run(['paths' => [$root], 'chunk_size' => 8], $vault, 'pw');

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        $chunks = $manifest['files'][0]['chunks'];

        return [$vault, new SubstitutingVault($vault, $chunks[0], $chunks[1])];
    }

    /**
     * The same substitution, on the SECOND of two files — the shape that
     * leaves a partial tree when writes happen inline.
     *
     * @return array{0: InMemoryVault, 1: SubstitutingVault}
     */
    private static function twoFileSetWithSubstitutionOnTheSecondFile(): array
    {
        $root = sys_get_temp_dir().'/p4-restore-pair-'.uniqid();
        mkdir($root, 0777, true);
        file_put_contents($root.'/a.txt', 'first file');
        file_put_contents($root.'/b.txt', 'CCCCCCCC'.'DDDDDDDD');

        $vault = new InMemoryVault;
        BackupRunner::run(['paths' => [$root], 'chunk_size' => 8], $vault, 'pw');

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);

        // Whichever entry is second in manifest order is the one to poison.
        $victim = $manifest['files'][1];
        $chunks = $victim['chunks'];
        self::assertCount(2, $chunks, 'the second file needs two chunks to substitute between');

        return [$vault, new SubstitutingVault($vault, $chunks[0], $chunks[1])];
    }
}

/**
 * Vault double that flips the first ciphertext byte of every stored chunk
 * (corrupts the secretstream header) to simulate in-transit tampering.
 */
final class CorruptingVault implements VaultInterface
{
    use RetentionStubTrait;

    private VaultInterface $inner;

    public function __construct(VaultInterface $inner)
    {
        $this->inner = $inner;
    }

    public function putChunk(string $hash, string $bytes): string
    {
        // CORRUPT BY FLIPPING A BIT, NOT BY ASSIGNING A CONSTANT.
        //
        // This read `return $this->inner->putChunk($hash, "\xff" . substr($bytes, 1));`
        // which is a NO-OP whenever the first ciphertext byte already IS 0xff —
        // a 1-in-256 event per chunk. Nothing was then corrupted, decryption
        // correctly succeeded, and the tamper test failed. GitLab pipeline #216
        // hit it on verify:8.4: 73 occurrences in 20,000 trials, so a green run
        // of this test proved less than it appeared to about half the time per
        // run it happened to be exercised.
        //
        // XOR with 0xff is guaranteed to change the byte, whatever it was.
        $bytes[0] = $bytes[0] ^ chr(0xFF);

        return $this->inner->putChunk($hash, $bytes);
    }

    public function getChunk(string $hash): string
    {
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

    public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
    {
        return $this->inner->listManifests($limit, $unreadableIds);
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

/**
 * Vault double that serves ONE chunk's ciphertext under a DIFFERENT chunk's
 * content address — what an attacker with post access to the backup channel
 * does by downloading a document and re-uploading it under another chunk's
 * name.
 *
 * Every byte it serves is AUTHENTIC (the AEAD decrypts it cleanly) and every
 * byte is WRONG for the address asked for. Only a comparison of the decrypted
 * plaintext against the manifest's content address can tell the difference,
 * which is why this fixture exists: it is the one corruption the AEAD
 * structurally cannot catch.
 */
final class SubstitutingVault implements VaultInterface
{
    use RetentionStubTrait;

    private VaultInterface $inner;

    /** the content address whose bytes are swapped out */
    private string $servedAt;

    /** the content address whose bytes are served instead */
    private string $servedFrom;

    public function __construct(VaultInterface $inner, string $servedAt, string $servedFrom)
    {
        $this->inner = $inner;
        $this->servedAt = $servedAt;
        $this->servedFrom = $servedFrom;
    }

    public function getLatestManifest(): ?array
    {
        return $this->inner->getLatestManifest();
    }

    public function getChunk(string $hash): string
    {
        return $this->inner->getChunk($hash === $this->servedAt ? $this->servedFrom : $hash);
    }

    public function putChunk(string $hash, string $bytes): string
    {
        return $this->inner->putChunk($hash, $bytes);
    }

    public function putManifest(string $json): string
    {
        return $this->inner->putManifest($json);
    }

    public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
    {
        return $this->inner->listManifests($limit, $unreadableIds);
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
