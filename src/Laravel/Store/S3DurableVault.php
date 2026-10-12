<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Store;

use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use RuntimeException;

/**
 * S3 AS A DURABLE BACKUP TIER (Cycle 38 — Goal D, verbatim 2026-09-24:38-47
 * "converts telegram into a permanent backup storage with ftp or rsync or
 * s3 api … THE S3 IS GOOD and it should treat telegram as a backup
 * storage"; anchor verbatim 2026-09-27:628 "we must get ready for first
 * version usage").
 *
 * THE DEFECT THIS CLOSES: `S3GatewayContract`'s only consumer in the whole
 * package was `MinioHotCache` — the TStore HOT CACHE, which is by
 * definition evictable (TTL + LRU) and is not a backup. So the operator's
 * S3 never reached the durable path: `teleframe:backup` could write to
 * Telegram or to a process-local InMemoryVault and to nothing else. This
 * class is the third transport, so "teleframe:backup --driver=s3" is a
 * REAL backup tier: it is the same `BackupRunner`, the same
 * `VaultCrypto::encryptChunk` sealing and the same dedup a Telegram run
 * gets, with the object bytes landing in the configured bucket instead of
 * in a channel.
 *
 * WHY IT IMPLEMENTS THE ENGINE'S `VaultInterface` AND NOT A SIDE CHANNEL:
 * the interface's own docblock says message ids are "opaque strings to
 * keep the interface transport-agnostic", and it already has two
 * transports. A run written through this class is therefore readable by
 * the shipped `Restorer`, the shipped `Pruner` and the shipped
 * `teleframe:backup` command with no branch anywhere in the engine. The
 * layering matches `MinioHotCache` exactly: the ENGINE never learns about
 * S3, and the Laravel Store layer adapts it. Nothing under `src/Teleframe`
 * or `src/Core` was touched, and no `preg_*` is used (src/ is regex-free
 * outside the Laravel/Schema/Bot allow-list).
 *
 * OBJECT LAYOUT (flat, under one configured bucket — the gateway is
 * already bucket-scoped, so the leading segment is a set namespace):
 *
 *   teleframe-backup/<setId>/chunks/<ciphertext sha256>
 *   teleframe-backup/<setId>/manifests/<zero-padded sequence>
 *
 * A chunk object is named by the CIPHERTEXT hash, never by the plaintext
 * content address the manifest records (`VaultInterface::putChunk`, and
 * the rule TelegramVault/InMemoryVault follow): a document named by its
 * plaintext hash confirms a chosen plaintext to anyone who can list the
 * bucket. The plaintext → ciphertext book travels in the manifest's
 * `wire_chunks`, exactly as on the other two tiers.
 *
 * ORDERING, AND WHY IT IS NOT "NEWEST WRITE WINS": the gateway's
 * `list()` is a flat key listing with no ordering guarantee, and unlike a
 * Telegram channel there is no message id to lean on. Manifests are
 * therefore ranked by the `generation` inside the body, with ties broken on
 * the newest post — the same rule `TelegramVault` uses, and the reason is
 * identical: a rollback must not become truth. The post-ordering sequence in
 * the key exists only to break those ties and to make the newest-post
 * failure loud.
 *
 * THE `generation` IS A WRITE PRIMITIVE, SO IT IS ONLY HONEST WHEN SEALED.
 * The ranking above is only as trustworthy as the body it ranks. An
 * UNSEALED manifest is attacker-writable: a manifest injected into the
 * bucket with `generation => PHP_INT_MAX` is read back as 9223372036854775807
 * by a FRESH process, permanently pinning "latest" to that content for the
 * life of the set, with no exception and no log line. That was not a
 * disclosure limit, it was a write primitive, and it is why sealing is not
 * optional decoration here. With a passphrase configured the body is
 * AEAD-sealed, so `generation` cannot be forged without the secret and the
 * ranking is exactly as trustworthy as on the Telegram tier.
 *
 * FAULT BEHAVIOUR IS THE OPPOSITE OF THE HOT CACHE, DELIBERATELY.
 * `MinioHotCache` swallows a down gateway because a cold cache must fall
 * through to Telegram. A BACKUP tier has no such fallback: a silently
 * swallowed write is a backup that reported success and stored nothing —
 * the exact lie this class exists to end. So every gateway call here
 * propagates, and a miss is a `RuntimeException` rather than an empty
 * string. Only the S3 client's own miss-or-error `false` is interpreted,
 * and only as a miss.
 *
 * MANIFEST CONFIDENTIALITY, STATED AS WHAT IS ACTUALLY TRUE (Cycle 39 —
 * Goal F). The manifest body is AEAD-SEALED — `VaultCrypto::encryptChunk`
 * under `VaultCrypto::deriveManifestKey($setId, $passphrase)`, the identical
 * pair `TelegramVault::putManifest` uses — WHENEVER the vault is
 * constructed with a passphrase, which is what the container's vault factory
 * now does for `--driver=s3` using the `teleframe.backup.passphrase` it had
 * already resolved. That matters for more than secrecy: an unsealed body
 * carries a FORGEABLE `generation`, and the ranking above is a write
 * primitive rather than a disclosure. The chunk bodies were always sealed;
 * now the index is too.
 *
 * THE PASSPHRASE IS OPTIONAL, AND WHAT THAT MEANS IS EXACTLY THIS: a vault
 * constructed WITHOUT one stores and reads plaintext JSON, unchanged — the
 * behaviour the pre-existing eight tests in S3DurableTierTest pin, and the
 * behaviour that keeps this tier plain-PHP-constructible with no secret in
 * hand. In that mode the `generation` is a bare, forgeable int and the
 * bucket's own integrity is the only thing standing behind "latest"; the
 * CHUNKS are still sealed either way. An operator running a set on the
 * unsealed mode is trusting the bucket, exactly as the class docblock used
 * to say in the negative.
 *
 * SEALING IS A ONE-WAY SWITCH PER SET, STATED PLAINLY: when a passphrase is
 * configured, a manifest object that does not decrypt is UNREADABLE and is
 * never re-interpreted as plaintext JSON, because accepting a plaintext body
 * as a legacy fallback would hand back the exact forgery the seal closes.
 * So a set whose manifests were written unsealed must be read with the same
 * (no) passphrase it was written with, and turning sealing on later does not
 * retroactively protect the objects already in the bucket.
 */
final class S3DurableVault implements VaultInterface
{
    /** The same manifest marker the other transports carry (VaultInterface's wire contract). */
    public const MANIFEST_MARKER = TelegramVault::MANIFEST_MARKER;

    /** Object-key namespace: this tier's entries, per backup set. */
    private const NAMESPACE = 'teleframe-backup';

    /** Manifest post-ordering: a zero-padded integer so the key sorts as a number. */
    private const SEQUENCE_WIDTH = 16;

    /**
     * How many slots putManifest will try to CLAIM before giving up. Each
     * loss means a concurrent writer took that slot inside our list()→put()
     * window, so the next attempt legitimately re-reads the listing and
     * takes the following slot; a writer that keeps losing to live writers
     * is a real fault, not something to spin on forever.
     */
    private const MANIFEST_SLOT_ATTEMPTS = 5;

    /** Highest manifest generation this instance has accepted (rollback tripwire, TelegramVault's rule). */
    private int $highestGeneration = 0;

    /** @var array<string, string>|null plaintext sha256 → ciphertext sha256 (the manifest's wire_chunks) */
    private ?array $wireChunkMap = null;

    /**
     * @param  string|null  $manifestPassphrase  the set passphrase the manifest INDEX is sealed
     *                                           under (VaultCrypto::deriveManifestKey, exactly as
     *                                           TelegramVault does). OPTIONAL, and null means
     *                                           the pre-existing plaintext behaviour, unchanged:
     *                                           the gateway stays reachable as a plain-PHP-
     *                                           constructible tier with no secret in hand.
     */
    public function __construct(
        private readonly string $setId,
        private readonly S3GatewayContract $gateway,
        private readonly ?string $manifestPassphrase = null,
    ) {}

    /** The gateway this vault writes through — the container's, never a private one. */
    public function gateway(): S3GatewayContract
    {
        return $this->gateway;
    }

    public function putChunk(string $hash, string $bytes): string
    {
        $this->gateway->put(self::chunkKey($this->setId, $hash), $bytes);

        return $hash;
    }

    /**
     * Fetch chunk bytes by their PLAINTEXT content address — the address
     * the manifest records — resolving it to the CIPHERTEXT name the
     * object was actually stored under. A manifest written before
     * ciphertext addressing carries no address map, and there the two
     * are the same string.
     *
     * @throws RuntimeException when the chunk is not in the bucket
     */
    public function getChunk(string $hash): string
    {
        $name = $this->wireChunkMap()[$hash] ?? $hash;
        $bytes = $this->gateway->get(self::chunkKey($this->setId, $name));
        if ($bytes === false) {
            throw new RuntimeException("chunk {$hash} not found in backup set {$this->setId}");
        }

        return $bytes;
    }

    /**
     * The wire (ciphertext) object names for the given PLAINTEXT content
     * addresses, in the same order. Duck-typed by `Pruner` (the same
     * method TelegramVault and InMemoryVault expose) so the chunk GC
     * keeps objects by the name they actually have in the bucket —
     * without it every live chunk would look like an orphan.
     *
     * @param  list<mixed>  $plainHashes
     * @return list<string>
     */
    public function wireNamesFor(array $plainHashes): array
    {
        $map = $this->wireChunkMap();

        $names = [];
        foreach ($plainHashes as $hash) {
            if (is_string($hash)) {
                $names[] = $map[$hash] ?? $hash;
            }
        }

        return $names;
    }

    /**
     * PUBLISH A MANIFEST INTO A SLOT THIS CALLER ACTUALLY OWNS.
     *
     * `S3GatewayContract` is put/get/delete/list — there is NO
     * compare-and-set primitive in this seam, so the old
     * read-the-listing-take-max+1 write was a read-then-write race: two
     * concurrent runs (which the Redis per-set lock does NOT prevent —
     * `BackupRunner` deliberately proceeds UNLOCKED when the lock is
     * unavailable) picked the same sequence, the second `put` overwrote the
     * first, and a whole generation vanished while both runs reported
     * success. The contract needs no new method: put, then READ BACK the
     * slot and check the bytes are ours. A writer that lost the race sees
     * someone else's bytes, and takes the next slot. The seal (when one is
     * configured) is applied ONCE outside the loop precisely so every retry
     * writes the identical blob and the read-back comparison is an exact
     * byte equality rather than a coin flip on fresh AEAD nonces.
     */
    public function putManifest(string $json): string
    {
        $stored = $this->sealManifest($json);

        for ($attempt = 0; $attempt < self::MANIFEST_SLOT_ATTEMPTS; $attempt++) {
            $sequence = $this->nextManifestSequence();
            $key = self::manifestKey($this->setId, $sequence);

            // Step 1 — was the slot still FREE when we got here? Our listing
            // may be stale (another writer's put can land between its list()
            // and ours), and writing over a live manifest is the loss this
            // whole method exists to prevent: the previous occupant's whole
            // generation would vanish and its writer would never know.
            if ($this->gateway->get($key) !== false) {
                continue;
            }

            // Step 2 — take it, and do not clobber the store while doing so.
            $this->gateway->put($key, $stored);

            // Step 3 — did WE win the slot? A writer that raced us has
            // already replaced our bytes, and "latest" must not silently
            // become a manifest this run never wrote.
            if ($this->gateway->get($key) === $stored) {
                $this->wireChunkMap = null; // the next manifest rewrites the address book

                return (string) $sequence;
            }
        }

        throw new RuntimeException(
            "could not claim a manifest slot for backup set {$this->setId} after ".self::MANIFEST_SLOT_ATTEMPTS
            .' attempts: every candidate sequence was taken by a concurrent writer between the listing and the write',
        );
    }

    /**
     * THE MANIFEST WITH THE HIGHEST GENERATION WINS — not the newest
     * object write, and not the highest key suffix. `VaultInterface`
     * requires exactly this, and on this tier it is load-bearing: the
     * gateway lists flat keys with no recency signal, so an
     * implementation that trusted the listing would let a rollback (or a
     * re-upload of an older manifest) become the truth.
     *
     * A generation this instance has already accepted and now sees lower
     * is refused outright — `TelegramVault`'s tripwire, kept so the two
     * tiers cannot disagree about what "latest" means.
     *
     * @return array<string, mixed>|null
     *
     * @throws RuntimeException on a generation rollback, or an unreadable NEWEST manifest post
     */
    public function getLatestManifest(): ?array
    {
        $unreadable = [];
        $read = $this->readManifests($unreadable);

        $best = $read[0]['data'] ?? null;
        if ($best === null) {
            return null;
        }

        $bestGeneration = $read[0]['generation'];
        if ($bestGeneration < $this->highestGeneration) {
            throw new RuntimeException(
                "manifest generation rollback refused for set {$this->setId}: the bucket's highest-generation manifest is "
                ."generation {$bestGeneration} but generation {$this->highestGeneration} was already accepted",
            );
        }
        $this->highestGeneration = $bestGeneration;

        $this->wireChunkMap = self::invertWireChunks($best['wire_chunks'] ?? null);

        return $best;
    }

    /**
     * THE BACKUP HISTORY, newest GENERATION first (VaultInterface).
     *
     * The bucket listing is a COMPLETE inventory (there is no search
     * index to lag, unlike a Telegram channel), so the same single walk
     * builds the present-object set the `restorable` check compares
     * against — one listing, not one per generation.
     *
     * @param  list<int>|null  $unreadableIds
     * @param-out list<int>  $unreadableIds
     * @return list<array{generation: int, created: string, files: int, chunks: int, bytes: int, restorable: bool, missing_chunks: int}>
     */
    public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
    {
        $limit = max(1, $limit);

        $present = [];
        foreach ($this->gateway->list() as $object) {
            $key = $object['key'];
            if (! self::isChunkKey($key, $this->setId)) {
                continue;
            }
            $present[self::bareName($key)] = true;
        }

        $unreadable = [];
        $read = $this->readManifests($unreadable);
        $unreadableIds = $unreadable;

        $rows = [];
        foreach ($read as $manifest) {
            $rows[] = self::historyRow($manifest['data'], $present);
            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * Vault entries by name prefix; an EMPTY prefix lists everything
     * (the prune GC walk). A chunk entry's name is its bare CIPHERTEXT
     * hash — `Pruner::isChunkName` matches on exactly 64 lowercase hex
     * chars, so a namespace-prefixed key would make every chunk
     * structurally invisible to the GC. A manifest entry's name is
     * deliberately NOT chunk-shaped, so the GC leaves the index alone.
     *
     * @return list<array<string, mixed>>
     */
    public function findMessagesByName(string $namePrefix): array
    {
        $entries = [];
        foreach ($this->gateway->list() as $object) {
            $key = $object['key'];
            if (! str_starts_with($key, self::prefixFor($this->setId))) {
                continue;
            }

            $name = self::isChunkKey($key, $this->setId)
                ? self::bareName($key)
                : 'm'.self::bareName($key);
            if (! str_starts_with($name, $namePrefix)) {
                continue;
            }

            $entries[] = ['id' => $name, 'name' => $name];
        }

        return $entries;
    }

    /**
     * Delete the object stored under $name (normally a bare ciphertext
     * chunk hash, as handed back by findMessagesByName). Idempotent: an
     * absent name is a no-op, so repeated prunes are safe.
     */
    public function delete(string $name): void
    {
        $this->gateway->delete(self::chunkKey($this->setId, $name));
    }

    /**
     * THE ONE place manifests are read: fetch every manifest object for
     * this set and rank them by the `generation` in the body — sealed, and
     * therefore unforgeable, whenever this vault holds a passphrase. Ties
     * break on the NEWEST post (highest sequence), which is the only
     * recency signal this transport has.
     *
     * An unreadable post (not JSON, or not decryptable under the configured
     * passphrase) is SKIPPED and its sequence collected into
     * $unreadableIds, with ONE exception: the newest post, which fails
     * loud. That post IS the current state, so an unreadable one is a real
     * fault and must not be papered over by promoting an older generation
     * to "latest".
     *
     * @param  list<int>  $unreadableIds
     * @return list<array{data: array<string, mixed>, generation: int, sequence: int}> newest generation first
     */
    private function readManifests(array &$unreadableIds): array
    {
        $sequences = [];
        $newest = 0;
        foreach ($this->gateway->list() as $object) {
            $key = $object['key'];
            if (! self::isManifestKey($key, $this->setId)) {
                continue;
            }
            $sequence = self::sequenceOf($key);
            if ($sequence === null) {
                continue;
            }
            $sequences[] = $sequence;
            $newest = max($newest, $sequence);
        }

        $unreadableIds = [];
        $read = [];
        foreach ($sequences as $sequence) {
            $bytes = $this->gateway->get(self::manifestKey($this->setId, $sequence));
            if ($bytes === false) {
                continue; // listed but unreadable: nothing to present, nothing to report as content
            }

            $decoded = $this->openManifest($bytes);
            if (! is_array($decoded)) {
                if ($sequence === $newest) {
                    throw new RuntimeException(
                        'latest manifest payload is not readable: it is neither JSON (unsealed mode) '
                        .'nor a blob sealed under this set\'s manifest passphrase (sealed mode)',
                    );
                }
                $unreadableIds[] = $sequence;

                continue;
            }

            $read[] = [
                'data' => $decoded,
                'generation' => self::generationOf($decoded),
                'sequence' => $sequence,
            ];
        }

        usort($read, static function (array $a, array $b): int {
            return $b['generation'] <=> $a['generation'] ?: $b['sequence'] <=> $a['sequence'];
        });

        return $read;
    }

    /**
     * One history row: the generation's OWN counts plus whether it can
     * still be restored — `TelegramVault::historyRow`'s rule verbatim,
     * including the "a manifest naming no chunks is not restorable" guard,
     * so `teleframe:backup history` reads identically on either tier.
     *
     * @param  array<string, mixed>  $manifest
     * @param  array<string, true>  $present
     * @return array{generation: int, created: string, files: int, chunks: int, bytes: int, restorable: bool, missing_chunks: int}
     */
    private static function historyRow(array $manifest, array $present): array
    {
        $files = $manifest['files'] ?? [];
        $files = is_array($files) ? array_values(array_filter($files, 'is_array')) : [];

        $bytes = 0;
        foreach ($files as $file) {
            $bytes += is_int($file['size'] ?? null) ? $file['size'] : 0;
        }

        $hashes = $manifest['chunk_hashes'] ?? [];
        $hashes = is_array($hashes) ? array_values(array_filter($hashes, 'is_string')) : [];

        $wireNames = self::invertWireChunks($manifest['wire_chunks'] ?? null);

        $missing = 0;
        foreach ($hashes as $hash) {
            if (! isset($present[$wireNames[$hash] ?? $hash])) {
                $missing++;
            }
        }

        $declared = count($hashes) > 0;

        return [
            'generation' => self::generationOf($manifest),
            'created' => is_string($manifest['created'] ?? null) ? $manifest['created'] : 'unknown',
            'files' => count($files),
            'chunks' => count($hashes),
            'bytes' => $bytes,
            'restorable' => $declared && $missing === 0,
            'missing_chunks' => $missing,
        ];
    }

    /** A manifest's monotonic generation; a manifest without one is generation 0. */
    private static function generationOf(array $manifest): int
    {
        return is_int($manifest['generation'] ?? null) ? $manifest['generation'] : 0;
    }

    /**
     * Seal a manifest body when a passphrase is configured, otherwise hand
     * the plaintext JSON over unchanged (the optional-passphrase contract).
     * The result is what lands in the bucket, and therefore what the slot
     * claim in putManifest() compares on read-back.
     */
    private function sealManifest(string $json): string
    {
        $key = $this->manifestSealKey();

        return $key === null ? $json : VaultCrypto::encryptChunk($key, $json);
    }

    /**
     * One stored object → its manifest array. With a passphrase the object
     * is AEAD-sealed and a body that does not decrypt is UNREADABLE — never
     * re-interpreted as plaintext JSON, because that fallback would restore
     * the very forgery the seal exists to close. Without one the object is
     * the JSON itself, and a non-JSON body is unreadable exactly as before.
     *
     * @return array<string, mixed>|null null = unreadable (see readManifests' newest-post rule)
     */
    private function openManifest(string $stored): ?array
    {
        $key = $this->manifestSealKey();
        if ($key !== null) {
            try {
                $stored = VaultCrypto::decryptChunk($key, $stored);
            } catch (RuntimeException) {
                return null;
            }
        }

        $decoded = json_decode($stored, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * The manifest INDEX's AEAD key, or null when this vault holds no
     * passphrase. The NULL case is not TelegramVault's behaviour and is
     * deliberately different: TelegramVault THROWS without a secret because
     * its channel is untrusted by construction, whereas this tier is
     * plain-PHP-constructible with no secret in hand and that is part of its
     * contract. What null costs is stated in the class docblock: the stored
     * body is plaintext, so `generation` is a forgeable write primitive and
     * the bucket's own integrity is all that backs "latest".
     */
    private function manifestSealKey(): ?string
    {
        if ($this->manifestPassphrase === null || $this->manifestPassphrase === '') {
            return null;
        }

        return VaultCrypto::deriveManifestKey($this->setId, $this->manifestPassphrase);
    }

    /**
     * @param  mixed  $wire  raw manifest data; only string→string pairs trusted
     * @return array<string, string> plaintext address → ciphertext name
     */
    private static function invertWireChunks(mixed $wire): array
    {
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
     * The (cached) plaintext → ciphertext address book of the latest
     * manifest, read the way a reader would. Only string→string pairs are
     * trusted; a mislabelled object still fails its own hash on decrypt.
     *
     * @return array<string, string>
     */
    private function wireChunkMap(): array
    {
        if ($this->wireChunkMap === null) {
            $this->getLatestManifest();
            $this->wireChunkMap ??= [];
        }

        return $this->wireChunkMap;
    }

    /** One past the highest manifest sequence already in the bucket. */
    private function nextManifestSequence(): int
    {
        $highest = 0;
        foreach ($this->gateway->list() as $object) {
            $key = $object['key'];
            if (! self::isManifestKey($key, $this->setId)) {
                continue;
            }
            $sequence = self::sequenceOf($key);
            if ($sequence !== null) {
                $highest = max($highest, $sequence);
            }
        }

        return $highest + 1;
    }

    private static function prefixFor(string $setId): string
    {
        return self::NAMESPACE.'/'.$setId.'/';
    }

    private static function chunkKey(string $setId, string $hash): string
    {
        return self::prefixFor($setId).'chunks/'.$hash;
    }

    private static function manifestKey(string $setId, int $sequence): string
    {
        return self::prefixFor($setId).'manifests/'.self::padSequence($sequence);
    }

    private static function padSequence(int $sequence): string
    {
        return str_pad((string) $sequence, self::SEQUENCE_WIDTH, '0', STR_PAD_LEFT);
    }

    private static function sequenceOf(string $key): ?int
    {
        $bare = self::bareName($key);
        if ($bare === '' || strspn($bare, '0123456789') !== strlen($bare)) {
            return null;
        }

        return (int) $bare;
    }

    /** The last path segment of a key — the chunk hash or the padded sequence. */
    private static function bareName(string $key): string
    {
        $cut = strrpos($key, '/');

        return $cut === false ? $key : substr($key, $cut + 1);
    }

    private static function isChunkKey(string $key, string $setId): bool
    {
        return str_starts_with($key, self::prefixFor($setId).'chunks/');
    }

    private static function isManifestKey(string $key, string $setId): bool
    {
        return str_starts_with($key, self::prefixFor($setId).'manifests/');
    }
}
