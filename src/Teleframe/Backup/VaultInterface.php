<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

/**
 * Content-addressed chunk + manifest storage behind a backup set
 * (plan Phase 4): BackupRunner uploads encrypted chunks keyed by their
 * hash and finishes with a manifest; Restorer reads the LATEST manifest
 * back and fetches the chunks it references. Message ids are opaque
 * strings to keep the interface transport-agnostic.
 */
interface VaultInterface
{
    /**
     * Store encrypted chunk bytes under their CIPHERTEXT hash.
     *
     * The name on the wire is never the plaintext content address: a
     * document named by its plaintext hash confirms a chosen plaintext to
     * anyone who can list the channel, at public chunk boundaries. The
     * ciphertext → plaintext mapping lives inside the sealed manifest
     * (BackupRunner's `wire_chunks`).
     *
     * @return string message id of the stored chunk document
     */
    public function putChunk(string $hash, string $bytes): string;

    /**
     * Fetch chunk bytes by their PLAINTEXT content address — the address
     * the manifest records — resolving it to the ciphertext name the
     * document was actually stored under. A manifest written before the
     * ciphertext-addressing change carries no address map, and there the
     * two are the same string.
     *
     * @throws \RuntimeException when the chunk is not in the vault
     */
    public function getChunk(string $hash): string;

    /**
     * Post a manifest JSON document; manifests form an ordered stack.
     * Implementations that talk to an untrusted channel MUST authenticate
     * the manifest with a secret the channel audience does not hold — the
     * manifest carries the file tree, the chunk order and the KDF salt.
     *
     * @return string message id of the manifest message
     */
    public function putManifest(string $json): string;

    /**
     * Decoded most-recent manifest. "Most recent" means highest
     * `generation` (monotonic, inside the sealed body), not highest
     * message id, so a rollback cannot become truth.
     *
     * @return array<string, mixed>|null null when no manifest was posted yet
     */
    public function getLatestManifest(): ?array;

    /**
     * THE BACKUP HISTORY, newest generation first, capped at $limit rows.
     *
     * Backup staleness is checkable (teleframe:backup status fails when the
     * newest manifest is older than the staleness bound) but the set's
     * DEPTH was unanswerable: no surface reported how many generations
     * exist, how old the oldest is, or whether an old generation can still
     * be restored at all.
     *
     * ORDER IS BY THE SEALED `generation`, NOT BY MESSAGE ID. Message
     * order is not generation order: a rollback, a repost, or a restored
     * channel all make them diverge, and a history listed in message order
     * would present an OLD generation as the newest one.
     *
     * Each row carries the manifest's own numbers — `chunks` and `bytes`
     * are the generation's OWN, not the vault's totals — plus two
     * restorability keys:
     *
     * - `restorable`  bool: every chunk this generation references is still
     *   present in the vault. A generation whose chunks a later prune
     *   deleted CANNOT be restored, and that is the single most valuable
     *   thing this listing can say.
     * - `missing_chunks` int: how many referenced chunk documents are gone
     *   (0 when `restorable` is true).
     *
     * An UNREADABLE manifest post (not decryptable under the vault key, or
     * not JSON) is SKIPPED — it is never presented as history, and its
     * message ids are returned through $unreadableIds so the caller can
     * report them. Skipping silently is refused: a history with an
     * unmentioned hole is worse than one that says so.
     *
     * @param  int  $limit  maximum rows returned; values below 1 are raised to 1
     * @param  list<int>|null  $unreadableIds  filled with the message ids of
     *                                         manifest posts that could not be read
     * @return list<array{generation: int, created: string, files: int, chunks: int, bytes: int, restorable: bool, missing_chunks: int}>
     */
    public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array;

    /**
     * The newest $generations manifest generations and their PLAINTEXT chunk
     * addresses, newest generation first. The retention keep-set is the UNION
     * of these rows' chunk_hashes.
     *
     * WHY THIS EXISTS ALONGSIDE listManifests(). listManifests() reports
     * COUNTS and a restorability verdict; it deliberately never hands back a
     * chunk address, so a keep-set cannot be built from it. Retention needs
     * the addresses of every RETAINED generation, not just the newest, and
     * a prune that kept only the newest made every older generation
     * unrestorable by construction.
     *
     * UNION, NOT NEWEST. Two generations that share a chunk name it once in
     * the keep-set; the same chunk in both rows is counted once, so a
     * generation is never dropped for "owning" a chunk another also owns,
     * and one is never double-counted either.
     *
     * Addresses are PLAINTEXT content addresses — the same strings
     * `chunk_hashes` carries in a manifest, and the same the prune compares
     * before resolving them to wire names (Pruner::keepSetFor asks the vault
     * for `wireNamesFor()`).
     *
     * Rows are ordered newest GENERATION first, the same order and the same
     * unreadable-post rule as listManifests() (an unreadable post is skipped,
     * and an unreadable NEWEST post fails loud).
     *
     * @param  int  $generations  raised to at least 1
     * @return list<array{generation: int, created: string, chunk_hashes: list<string>}>
     */
    public function listManifestChunkHashes(int $generations = 2): array;

    /**
     * List vault entries whose name starts with $namePrefix; an EMPTY
     * prefix lists everything in the vault (the prune GC walks that —
     * chunk GC needs a full inventory, and the vault API has no other
     * list-all surface). TelegramVault backs this with messages.search,
     * whose empty q returns every message per the Telegram docs, merged
     * with a realtime getHistory top-up (the channel text index lags).
     *
     * @return list<array<string, mixed>> entries carrying at least string
     *                                    id (opaque) and string name; the
     *                                    name is the chunk hash for chunk
     *                                    documents and the message text for
     *                                    manifest posts
     */
    public function findMessagesByName(string $namePrefix): array;

    /**
     * Delete the entry (chunk document) stored under $name. Idempotent:
     * an absent name is a no-op, so repeated prunes are safe.
     */
    public function delete(string $name): void;
}
