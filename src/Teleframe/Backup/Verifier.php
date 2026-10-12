<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use InvalidArgumentException;
use RuntimeException;

/**
 * Staleness / availability spot-check for a vault (plan Phase 4).
 *
 * verify() is KEYLESS — it samples N chunks from the latest manifest and
 * re-downloads each — but it is NOT integrity-blind. Documents are named
 * by their CIPHERTEXT hash and the manifest carries the ciphertext →
 * plaintext address book, so a keyless reader can still PROVE the bytes it
 * fetched are the bytes the (sealed) manifest names: sha256(fetched) must
 * equal that wire hash. A mismatch is reported as `corrupt` and is never
 * counted as `ok`; a verifier that reports tampered data as ok is worse
 * than no verifier, because its result is believed.
 *
 * A LEGACY manifest (pre-ciphertext-addressing: no `wire_chunks`, chunks
 * named by their plaintext hash) gives a keyless reader nothing to compare
 * against; those samples stay availability-only and are counted in
 * `checked` — full integrity for them is verifyWithKey()'s job.
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
     * Keyless verification: availability PLUS ciphertext-address integrity
     * wherever the manifest provides an address to check against.
     *
     * @param  callable(list<string> $hashes, int $n): list<string>|null  $rng
     * @return array{checked: int, ok: int, missing: int, corrupt: int}
     */
    public static function verify(VaultInterface $vault, int $sampleN = 5, ?callable $rng = null): array
    {
        $sample = self::sample($vault, $sampleN, $rng);
        $wire = self::wireChunkMap($sample->manifest);

        $ok = 0;
        $missing = 0;
        $corrupt = 0;

        foreach ($sample->hashes as $hash) {
            try {
                $bytes = $vault->getChunk($hash);
            } catch (RuntimeException) {
                $missing++;

                continue;
            }

            $expectedWire = $wire[$hash] ?? null;
            if ($expectedWire !== null && ! hash_equals($expectedWire, hash('sha256', $bytes))) {
                // Present, plausible, and NOT the bytes the manifest names:
                // corruption or tampering, never availability.
                $corrupt++;

                continue;
            }

            if ($bytes !== '') {
                $ok++;
            } else {
                $corrupt++;
            }
        }

        return ['checked' => count($sample->hashes), 'ok' => $ok, 'missing' => $missing, 'corrupt' => $corrupt];
    }

    /**
     * Full-integrity verification: derive the set key from the manifest
     * salt, decrypt every sampled chunk and compare sha256(plaintext)
     * against its content address.
     *
     * @param  callable(list<string> $hashes, int $n): list<string>|null  $rng
     * @return array{checked: int, ok: int, missing: int, corrupt: int}
     */
    public static function verifyWithKey(VaultInterface $vault, string $passphrase, int $sampleN = 5, ?callable $rng = null): array
    {
        $sample = self::sample($vault, $sampleN, $rng);

        $saltHex = $sample->manifest['salt'] ?? null;
        if (! is_string($saltHex) || strlen($saltHex) !== 32 || ! ctype_xdigit($saltHex)) {
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
     * The manifest's plaintext → ciphertext chunk address map (only
     * string→string pairs are trusted; the manifest is data whatever
     * sealed it). Empty for a legacy manifest, which then gets the
     * availability-only treatment.
     *
     * @param  array<string, mixed>  $manifest
     * @return array<string, string>
     */
    private static function wireChunkMap(array $manifest): array
    {
        $wire = $manifest['wire_chunks'] ?? null;
        if (! is_array($wire)) {
            return [];
        }

        $inverted = [];
        foreach ($wire as $wireHash => $plainHash) {
            if (is_string($wireHash) && is_string($plainHash)) {
                $inverted[$plainHash] = $wireHash;
            }
        }

        return $inverted;
    }

    /**
     * Chunk hashes + the manifest they came from, sampled down to $sampleN.
     *
     * @param  callable(list<string>, int): list<string>|null  $rng
     * @return object{hashes: list<string>, manifest: array<string, mixed>}
     */
    private static function sample(VaultInterface $vault, int $sampleN, ?callable $rng): object
    {
        if ($sampleN < 1) {
            throw new InvalidArgumentException('sampleN must be at least 1, got '.$sampleN);
        }

        $manifest = $vault->getLatestManifest();
        if ($manifest === null) {
            throw new RuntimeException('no manifest to verify');
        }

        $all = $manifest['chunk_hashes'] ?? null;
        if (! is_array($all)) {
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
