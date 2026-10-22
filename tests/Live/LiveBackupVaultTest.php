<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Live;

use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsLiveTelegram;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE VAULT'S REGISTERED LIVE GATE — the declared home friction F12-4 named.
 *
 * ## WHAT WAS MISSING, MEASURED, NOT GUESSED
 *
 * F12-4 in `.brain/evidence/2026-10-15-v1-2-audit.md` read: *"No registered live
 * gate for the vault."* The vault's live proof existed as a CLASS —
 * `tests/Backup/LiveVaultSmokeTest.php`, driving the real `scopeApi` map — but it
 * was keyed on a private `TELECLIENT_LIVE` flag that the shipped registry knows
 * nothing about, it read its credentials out of a SIBLING CLONE'S `.env` by
 * path, and it reached the skip API from outside the one sanctioned helper. So
 * it was a live gate in substance and an unregistered one in fact: no step name
 * existed that an operator could declare, no policy gate stood between the test
 * and `upload.saveFilePart`, and `.env` — which `AGENTS.md` calls a credential
 * and the live lane exists to avoid — was the documented way to supply one.
 *
 * ## WHY A LOCAL FAKE CANNOT STAND IN HERE
 *
 * Every other proof of this vault is a fake: 184 counted local fixtures, all
 * driving an injected answerer. That is the right way to test the crypto, the
 * chunking, the manifest and the GC, and it proves none of the question the
 * vault actually raises — whether Telegram accepts a chunk upload this package
 * built and hands back the same bytes. Only this step can answer that, which is
 * why its absence was worth three points and why this file exists at all.
 *
 * ## WHY IT IS A STEP AND NOT A FLAG
 *
 * `requireLiveStep('backup_vault')` is the whole difference. The step name must
 * exist in `RunsLiveTelegram::liveKnownSteps()`, must be declared in
 * `TELEFRAME_LIVE_STEPS` or the run FAILS by name, and — because that step
 * declares a method surface in `liveStepMethods()` — every one of the nine
 * methods the vault dials is checked against the two allowlists at the gate
 * before the first byte moves. `LiveEndpointPolicy` is not widened by any of it:
 * all nine are denied by the warm lane, and they are permitted here only because
 * this step addresses a DATED channel it owns.
 *
 * ## WHAT IT LEAVES ON THE ACCOUNT
 *
 * One channel per day, titled `teleframe-smoke-YYYY-MM-DD`, created on first run
 * of that day, holding the 1 KB chunk and the manifest this test posted. That is
 * the honest cost of the proof: `channels.createChannel` mints a real broadcast
 * channel. It is bounded (one per day, not one per run), it is never addressed
 * again by a later day, and the account owner can delete every one of them. It
 * is opt-in behind `TELEFRAME_LIVE_TESTS=1` AND an explicit `backup_vault`
 * declaration — two variables, neither of which any other lane sets.
 */
final class LiveBackupVaultTest extends TestCase
{
    use RunsLiveTelegram;

    /** `messages.search` and the realtime history top-up both trail a fresh post. */
    private const RETRIES = 3;

    private const RETRY_DELAY = 3;

    /**
     * The round trip: post a chunk, read it back BYTE-FOR-BYTE, then post a
     * manifest naming it and prove the manifest is the LATEST one a fresh read
     * sees.
     *
     * The three assertions are three different claims, and the third is the one
     * that has never been made anywhere in this repository: that a manifest
     * posted to a real channel is discoverable again — that the search index and
     * the history top-up agree with each other, rather than a fake answerer
     * agreeing with itself.
     */
    #[Test]
    public function a_chunk_and_its_manifest_round_trip_through_a_real_channel(): void
    {
        self::requireLiveStep('backup_vault');

        $setId = self::liveVaultSetId();
        $vault = TelegramVault::forScope(self::liveUserScope(), $setId);

        $bytes = random_bytes(1024);
        $hash = hash('sha256', $bytes);

        $vault->putChunk($hash, $bytes);

        self::assertSame(
            $bytes,
            self::retry(fn (): string => $vault->getChunk($hash)),
            "the chunk uploaded to set {$setId} did not come back byte-for-byte: what Telegram "
            .'accepted is not what this package put in',
        );

        $manifest = ['version' => 1, 'kind' => 'live-smoke', 'chunks' => [$hash]];
        $vault->putManifest((string) json_encode($manifest, JSON_THROW_ON_ERROR));

        $latest = self::retry(function () use ($vault, $hash): array {
            $current = $vault->getLatestManifest() ?? [];

            if (! in_array($hash, $current['chunks'] ?? [], true)) {
                // The index trails writes: a latest manifest that does not name
                // the chunk this run just posted is STALE, not final. Failing
                // here instead of asserting an empty array is what distinguishes
                // "Telegram has not indexed it yet" from "this never worked".
                throw new RuntimeException(
                    'the freshly posted manifest is not yet the one a fresh read returns',
                );
            }

            return $current;
        });

        self::assertSame(
            $manifest,
            $latest,
            "the manifest read back from set {$setId} is not the one that was posted",
        );

        self::liveAppendReport(
            'backup_vault',
            sprintf(
                "set: %s\nchunk: %s (%d bytes, sha256 named)\nmanifest: version %d, %d chunk(s)\n"
                .'result: round trip verified against a real channel',
                $setId,
                substr($hash, 0, 12).'…',
                strlen($bytes),
                // Read off $manifest, not $latest: assertSame() has already
                // proved the two are the same array, so re-reading the fetched
                // copy would report what we POSTED and call it a readback.
                (int) $manifest['version'],
                count($manifest['chunks']),
            ),
        );
    }

    /**
     * Telegram indexes a post a moment after it lands, so a fresh read can be
     * briefly behind a write that already succeeded. Retry, and let the last
     * failure land rather than replacing it with a vaguer one.
     *
     * @template T
     *
     * @param  callable(): T  $fetch
     * @return T
     */
    private static function retry(callable $fetch): mixed
    {
        $last = new RuntimeException('retry: the fetch never ran');

        for ($attempt = 1; $attempt <= self::RETRIES; $attempt++) {
            try {
                return $fetch();
            } catch (RuntimeException $e) {
                $last = $e;

                if ($attempt < self::RETRIES) {
                    sleep(self::RETRY_DELAY);
                }
            }
        }

        throw $last;
    }
}
