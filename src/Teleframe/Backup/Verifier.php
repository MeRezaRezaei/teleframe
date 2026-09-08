<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use InvalidArgumentException;
use RuntimeException;

/**
 * Staleness / availability spot-check for a vault (plan Phase 4).
 *
 * verify() is deliberately KEYLESS: it samples N chunk hashes from the
 * latest manifest, re-downloads each, and counts a chunk ok when it is
 * available AND non-empty. Integrity is NOT re-proven here — it is
 * structural: every chunk is a single-message secretstream sealed with
 * TAG_FINAL, so any truncation/tampering fails loud at RESTORE time in
 * VaultCrypto::decryptChunk. This keeps the cheap routine check (run from
 * cron, no secrets on disk) separate from the expensive one.
 *
 * verifyWithKey() upgrades the check when the passphrase is at hand:
 * full decrypt + plaintext sha256 comparison against the chunk's content
 * address, reporting decrypt/hash failures as `corrupt`.
 *
 * Sampling defaults to shuffle + slice; $rng is injectable for
 * determinism (tests pass identity/first-N selectors). When the manifest
 * references fewer chunks than $sampleN, every chunk is checked.
 */
final class Verifier
{
    /**
     * Availability-only verification (no passphrase).
     *
     * @param callable(list<string> $hashes, int $n): list<string>|null $rng
     * @return array{checked: int, ok: int, missing: int}
     */
    public static function verify(VaultInterface $vault, int $sampleN = 5, ?callable $rng = null): array
    {
        $sample = self::sample($vault, $sampleN, $rng);
        $ok = 0;
        $missing = 0;

        foreach ($sample->hashes as $hash) {
            try {
                $bytes = $vault->getChunk($hash);
            } catch (RuntimeException) {
                $missing++;

                continue;
            }
            if ($bytes !== '') {
                $ok++;
            }
            // present but empty: counted in `checked` only — ok + missing
            // < checked is the signal to look closer (verifyWithKey).
        }

        return ['checked' => count($sample->hashes), 'ok' => $ok, 'missing' => $missing];
    }

    /**
     * Full-integrity verification: derive the set key from the manifest
     * salt, decrypt every sampled chunk and compare sha256(plaintext)
     * against its content address.
     *
     * @param callable(list<string> $hashes, int $n): list<string>|null $rng
     * @return array{checked: int, ok: int, missing: int, corrupt: int}
     */
    public static function verifyWithKey(VaultInterface $vault, string $passphrase, int $sampleN = 5, ?callable $rng = null): array
    {
        $sample = self::sample($vault, $sampleN, $rng);

        $saltHex = $sample->manifest['salt'] ?? null;
        if (!is_string($saltHex) || strlen($saltHex) !== 32 || !ctype_xdigit($saltHex)) {
            throw new RuntimeException('manifest is malformed: missing or invalid salt');
        }

        $key = VaultCrypto::deriveKey($passphrase, hex2bin($saltHex));

        $ok = 0;
        $missing = 0;
        $corrupt = 0;

        foreach ($sample->hashes as $hash) {
            try {
                $blob = $vault->getChunk($hash);
            } catch (RuntimeException) {
                $missing++;

                continue;
            }

            try {
                $plaintext = VaultCrypto::decryptChunk($key, $blob);
            } catch (RuntimeException) {
                $corrupt++;

                continue;
            }

            if (hash('sha256', $plaintext) === $hash) {
                $ok++;
            } else {
                $corrupt++;
            }
        }

        return ['checked' => count($sample->hashes), 'ok' => $ok, 'missing' => $missing, 'corrupt' => $corrupt];
    }

    /**
     * Chunk hashes + the manifest they came from, sampled down to $sampleN.
     *
     * @param callable(list<string>, int): list<string>|null $rng
     * @return object{hashes: list<string>, manifest: array<string, mixed>}
     */
    private static function sample(VaultInterface $vault, int $sampleN, ?callable $rng): object
    {
        if ($sampleN < 1) {
            throw new InvalidArgumentException('sampleN must be at least 1, got ' . $sampleN);
        }

        $manifest = $vault->getLatestManifest();
        if ($manifest === null) {
            throw new RuntimeException('no manifest to verify');
        }

        $all = $manifest['chunk_hashes'] ?? null;
        if (!is_array($all)) {
            throw new RuntimeException('manifest is malformed: chunk_hashes must be a list');
        }

        $hashes = [];
        foreach ($all as $hash) {
            if (is_string($hash) && $hash !== '') {
                $hashes[] = $hash;
            }
        }

        $n = min($sampleN, count($hashes));

        if ($n === 0) {
            $sampled = [];
        } elseif ($rng !== null) {
            $sampled = $rng($hashes, $n);
        } else {
            $shuffled = $hashes;
            shuffle($shuffled);
            $sampled = array_slice($shuffled, 0, $n);
        }

        /** @var list<string> $sampled */

        return (object) ['hashes' => $sampled, 'manifest' => $manifest];
    }
}
