<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Tests\Support\FakeVaultApi;
use PHPUnit\Framework\TestCase;

/**
 * L5 — THE THIRD DIFFERENTIAL LANE: `InMemoryVault` vs `TelegramVault`.
 *
 * ## WHY THIS IS POSSIBLE AT ALL, and why it matters that it is
 *
 * `TelegramVault` takes its whole Telegram surface as **injected callables** in `$api`
 * — `uploadBytes`, `sendDocument`, `sendText`, `listHistoryPage`, `deleteMessages`,
 * `findChannel`, `createChannel`, `findMessagesByName`. That means the REAL vault is
 * drivable **offline, with no credentials and no network**, against `tests/Support/
 * FakeVaultApi`.
 *
 * So this is not a "test the double in isolation" lane. It runs the **same operations
 * through both `VaultInterface` implementations** and requires them to agree — which is
 * the only way to know the double has not drifted from the thing it stands in for.
 *
 * **15 test files trust `InMemoryVault`.** If it disagrees with `TelegramVault` anywhere
 * the backup set behaves differently depending on which is configured — and every one of
 * those 15 files would still be green.
 *
 * ## WHAT THE PREVIOUS TWO LANES FOUND, for calibration
 *
 * - `CacheDifferentialTest` → a **shipped defect** (`FilesystemCache` TTL≤0 = forever).
 * - `FakeDispatcherShapeDifferentialTest` → a **structural testability gap** (the double
 *   cannot express bus entry identity at all).
 *
 * Both were found in under an hour by running operations twice. This lane is the third
 * instance of that method and the same method applies here.
 *
 * ## THE RULING
 *
 * Agreement is the requirement; where the two differ the difference is either a recorded
 * DIVERGENCE (with the reason) or a failure. Nothing here asserts that one side is
 * "correct" unless the interface or the documented wire contract says so.
 */
final class VaultDifferentialTest extends TestCase
{
    private const PASSPHRASE = 'test-passphrase';

    private FakeVaultApi $api;

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FakeVaultApi;
    }

    /** The double, and the real thing, both as the same interface. */
    private function memory(): VaultInterface
    {
        return new InMemoryVault;
    }

    private function telegram(): VaultInterface
    {
        $this->api->addChannel('teleframe-backup:set1');

        return new TelegramVault('set1', $this->api->map(), self::PASSPHRASE);
    }

    /**
     * Run the same operation against both vaults and require the same result.
     *
     * @param  callable(VaultInterface): mixed  $operation
     */
    private function assertBothAgree(callable $operation, string $what): void
    {
        $memory = $operation($this->memory());
        $telegram = $operation($this->telegram());

        self::assertSame(
            $memory,
            $telegram,
            sprintf(
                'L5 DIFFERENTIAL: InMemoryVault and TelegramVault disagree on "%s". 15 test files '
                .'trust the double, so a divergence means the backup set behaves differently '
                .'depending on which VaultInterface is configured.',
                $what,
            ),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // THE ROUNDTRIP THAT 15 TEST FILES DEPEND ON
    // ─────────────────────────────────────────────────────────────────────────────

    /** putChunk then getChunk returns the bytes that went in. */
    public function test_a_chunk_round_trips_through_both_implementations(): void
    {
        $this->assertBothAgree(
            static function (VaultInterface $v): string {
                $id = $v->putChunk('hash-a', 'bytes-a');

                return $v->getChunk('hash-a').'|'.$id;
            },
            'a chunk round-trips and yields a message id',
        );
    }

    /** Two different chunks keep their own bytes — no cross-contamination. */
    public function test_two_chunks_do_not_contaminate_each_other(): void
    {
        $this->assertBothAgree(
            static function (VaultInterface $v): string {
                $v->putChunk('hash-a', 'bytes-a');
                $v->putChunk('hash-b', 'bytes-b');

                return $v->getChunk('hash-a').'|'.$v->getChunk('hash-b');
            },
            'two chunks keep separate bytes',
        );
    }

    /** A manifest round-trips as the same JSON the caller stored. */
    public function test_a_manifest_round_trips_through_both_implementations(): void
    {
        $json = json_encode(['set' => 'set1', 'chunks' => ['hash-a']], JSON_THROW_ON_ERROR);

        $this->assertBothAgree(
            static function (VaultInterface $v) use ($json): string {
                $v->putManifest($json);

                return json_encode($v->getLatestManifest());
            },
            'a manifest round-trips',
        );
    }

    /** Two manifests: the latest wins, and both remain listable. */
    public function test_the_latest_manifest_wins_and_both_remain_listable(): void
    {
        $first = json_encode(['generation' => 1], JSON_THROW_ON_ERROR);
        $second = json_encode(['generation' => 2], JSON_THROW_ON_ERROR);

        $this->assertBothAgree(
            static function (VaultInterface $v) use ($first, $second): string {
                $v->putManifest($first);
                $v->putManifest($second);
                $listed = $v->listManifests(10);

                return json_encode($v->getLatestManifest()).'|'.count($listed);
            },
            'the latest manifest is returned and both are listed',
        );
    }

    /** An empty vault reports no manifests — the state a fresh backup starts from. */
    public function test_an_empty_vault_lists_no_manifests(): void
    {
        $this->assertBothAgree(
            static fn (VaultInterface $v): int => count($v->listManifests(10)),
            'an empty vault lists no manifests',
        );
    }

    /** listManifests honours its limit on both sides. */
    public function test_list_manifests_honours_its_limit(): void
    {
        $this->assertBothAgree(
            static function (VaultInterface $v): string {
                for ($i = 1; $i <= 3; $i++) {
                    $v->putManifest(json_encode(['generation' => $i], JSON_THROW_ON_ERROR));
                }

                return count($v->listManifests(2)).'|'.count($v->listManifests(10));
            },
            'listManifests honours its limit',
        );
    }

    /** A chunk written before any manifest is still retrievable. */
    public function test_a_chunk_is_retrievable_before_any_manifest_exists(): void
    {
        $this->assertBothAgree(
            static function (VaultInterface $v): string {
                $v->putChunk('hash-a', 'bytes-a');

                return $v->getChunk('hash-a');
            },
            'a chunk is retrievable before any manifest exists',
        );
    }

    /** findMessagesByName returns nothing for a prefix that was never stored. */
    public function test_finding_an_unknown_name_returns_nothing_on_both(): void
    {
        $this->assertBothAgree(
            static fn (VaultInterface $v): int => count($v->findMessagesByName('nothing-matches')),
            'an unknown name matches nothing',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // THE MARKER — A WIRE CONSTANT BOTH MUST AGREE ON, OFFLINE
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * The double declares its manifest marker AS the real vault's constant
     * (`InMemoryVault.php:21`), so it cannot drift by accident — and this pins the value
     * against the real class rather than against the double.
     *
     * If the marker changed, every manifest already stored in a live vault would become
     * unreadable, and the double would follow it silently.
     */
    public function test_the_manifest_marker_is_the_real_vaults_constant(): void
    {
        self::assertSame(
            TelegramVault::MANIFEST_MARKER,
            InMemoryVault::MANIFEST_MARKER,
            'the double must use the real vault\'s marker, or manifests written by one '
            .'implementation are invisible to the other',
        );
    }

    /** And the marker is a stable, non-empty prefix — a manifest is recognisable by it. */
    public function test_the_manifest_marker_is_a_stable_non_empty_prefix(): void
    {
        $marker = TelegramVault::MANIFEST_MARKER;

        self::assertNotSame('', $marker, 'an empty marker cannot distinguish a manifest');
        self::assertSame('TBMANIFEST1:', $marker, 'the marker is a wire constant; changing it orphans live manifests');
    }

    /**
     * The double must not re-declare the constant — it must reference the real one, so
     * there is exactly one place to change.
     */
    public function test_the_double_references_the_marker_rather_than_copying_it(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/src/Teleframe/Backup/InMemoryVault.php');

        self::assertStringContainsString(
            'MANIFEST_MARKER = TelegramVault::MANIFEST_MARKER',
            $source,
            'the double must reference TelegramVault::MANIFEST_MARKER, not re-declare the literal',
        );
    }
}
