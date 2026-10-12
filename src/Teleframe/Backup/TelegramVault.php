<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use RuntimeException;

/**
 * Telegram-channel-backed vault (plan Phase 4, ported from the owner's
 * sdd/backup TelegramVault design onto teleframe primitives): one private
 * broadcast channel per backup set, titled CHANNEL_PREFIX + setId. Chunks
 * are force_file documents NAMED by their content hash (the on-channel name
 * must stay byte-identical to the hash the prune GC compares against its
 * manifest keep-set; the redundant plaintext hash CAPTION is dropped for a
 * benign label instead), manifests are text messages carrying MANIFEST_MARKER
 * + base64 of an AEAD-SEALED manifest blob (XChaCha20-Poly1305 keyed by a
 * channel-scoped domain key — never the plaintext JSON with its paths and
 * salt). Latest manifest wins, chunk lookup is exact-name, marker detection
 * is str_starts_with/substr only (src/ is regex-free by policy).
 *
 * Every teleframe call lives behind an injectable call map so tests run
 * offline; scopeApi()/forScope() build that map from a real UserAccount-
 * Scope/BotAccountScope using teleframe's actual surfaces: the
 * upload.saveFilePart/saveBigFilePart part loop (StorageMedia part math),
 * messages.sendMedia with inputMediaUploadedDocument, messages.sendMes-
 * sage, messages.search, messages.deleteMessages and the upload.getFile
 * download loop.
 *
 * MANIFEST TRUST (findings H5 + rollback): the manifest is the index of
 * the whole set — file tree, per-file chunk order, and the Argon2id salt
 * — so it is AEAD-sealed under a key derived from the set PASSPHRASE
 * (VaultCrypto::deriveManifestKey), never from the channel access hash,
 * which Telegram hands to every account that can SEE the channel and
 * which therefore authenticates nothing. A vault constructed without a
 * passphrase cannot read or write manifests and says so, rather than
 * falling back to a globally computable key. Manifests additionally
 * carry a monotonic `generation` so a rollback to an older (or forged,
 * empty) manifest is refused instead of becoming truth.
 */
final class TelegramVault implements VaultInterface
{
    public const CHANNEL_PREFIX = 'teleframe-backup:';

    public const MANIFEST_MARKER = 'TBMANIFEST1:';

    public const CHANNEL_ABOUT = 'teleframe encrypted backup vault';

    public const CHUNK_CAPTION = 'teleframe encrypted backup chunk';

    private const SEARCH_LIMIT = 100;

    private const LIST_PAGE_SIZE = 100; // getHistory page size of the uncapped prune walk

    private const LIST_HARD_CAP = 10000; // pathological-stream abort: throw, never truncate

    private const PART_SIZE = 524288; // 512 KB standard MTProto part size

    private const BIG_FILE_THRESHOLD = 10485760; // > 10 MB switches to saveBigFilePart

    private const API_KEYS = ['findChannel', 'createChannel', 'uploadBytes', 'sendDocument', 'sendText', 'findMessagesByName', 'deleteMessages', 'listHistoryPage'];

    private ?int $channelId = null;

    private int $channelAccessHash = 0;

    /** Highest manifest generation this instance has accepted (rollback tripwire). */
    private int $highestGeneration = 0;

    /** @var array<string, string>|null plaintext chunk sha256 → ciphertext sha256 (the manifest's wire_chunks) */
    private ?array $wireChunkMap = null;

    /**
     * @param  array<string, callable>  $api  injectable teleframe call map; see scopeApi()
     * @param  string|null  $manifestPassphrase  the set passphrase the manifest key is
     *                                           derived from. NULL = this vault cannot read or write manifests
     *                                           (putManifest()/getLatestManifest() throw): there is no secret to
     *                                           authenticate the index with, and a key taken from the channel would
     *                                           be one the channel audience already holds.
     *
     * @throws InvalidArgumentException when a required callable is missing
     */
    public function __construct(
        private readonly string $setId,
        private readonly array $api,
        private readonly ?string $manifestPassphrase = null,
    ) {
        $missing = [];
        foreach (self::API_KEYS as $key) {
            if (! array_key_exists($key, $this->api)) {
                $missing[] = $key;
            }
        }
        if ($missing !== []) {
            throw new InvalidArgumentException('vault api map is missing callable(s): '.implode(', ', $missing));
        }
    }

    /** Channel title for a backup set: CHANNEL_PREFIX + setId. */
    public static function channelTitle(string $setId): string
    {
        return self::CHANNEL_PREFIX.$setId;
    }

    /** Vault over a real scope: the default call map below. */
    public static function forScope(UserAccountScope $scope, string $setId, ?string $manifestPassphrase = null): self
    {
        return new self($setId, self::scopeApi($scope), $manifestPassphrase);
    }

    /**
     * The real teleframe call map (live path; offline tests inject fakes):
     *
     * - findChannel(title): ?array{id,access_hash}   dialog scan for exact-title channel
     * - createChannel(title, about): array{id,access_hash}  channels.createChannel broadcast
     * - uploadBytes(name, bytes): array   inputFile-ish (upload part loop)
     * - sendDocument(peer, inputFile, caption): int msg id
     * - sendText(peer, text): int    msg id
     * - findMessagesByName(peer, namePrefix, limit):
     *     list<array{id: int, name: string, fetch(name): string}>
     *     messages.search MERGED with a realtime getHistory top-up: the
     *     channel text index ingests posts minutes late (found live — the
     *     smoke saw fresh manifests missing from search for 30s+ while
     *     history had them instantly), so search alone cannot back the
     *     latest-wins manifest lookup. An EMPTY namePrefix is valid and
     *     returns every message: messages.search with an empty q lists
     *     all of a peer's messages per the Telegram docs (plus the
     *     history merge) — but the RESULT COUNT is capped at the passed
     *     limit (SEARCH_LIMIT for vault-internal find() calls), so the
     *     prune GC must NOT ride this call (see listHistoryPage).
     * - listHistoryPage(peer, limit, offsetId):
     *     list<array{id: int, name: string, fetch(name): string}>
     *     one messages.getHistory page strictly below offsetId (0 = new-
     *     est). listAllEntries() pages through it with the oldest-id
     *     cursor — the UNcapped inventory the prune GC walks.
     * - deleteMessages(peer, ids): array   messages.deleteMessages (revoke)
     *
     * @param  UserAccountScope  $scope  a BotAccountScope passes as-is (subclass)
     * @return array<string, callable>
     */
    public static function scopeApi(UserAccountScope $scope): array
    {
        return [
            'findChannel' => function (string $title) use ($scope): ?array {
                $dialogs = $scope->getDialogs(100);
                $chats = $dialogs['chats'] ?? [];
                if (! is_array($chats)) {
                    return null;
                }
                foreach ($chats as $chat) {
                    if (! is_array($chat) || ($chat['_'] ?? '') !== 'channel') {
                        continue;
                    }
                    if (($chat['title'] ?? null) === $title) {
                        return ['id' => (int) $chat['id'], 'access_hash' => (int) ($chat['access_hash'] ?? 0)];
                    }
                }

                return null;
            },
            'createChannel' => function (string $title, string $about) use ($scope): array {
                $updates = $scope->createChannel($title, $about);
                $chats = $updates['chats'] ?? [];
                $chat = is_array($chats) ? ($chats[0] ?? null) : null;
                if (is_array($chat) && isset($chat['id'])) {
                    return ['id' => (int) $chat['id'], 'access_hash' => (int) ($chat['access_hash'] ?? 0)];
                }

                throw new RuntimeException("failed to create backup channel {$title}");
            },
            'uploadBytes' => fn (string $name, string $bytes): array => self::uploadBytes($scope, $name, $bytes),
            'sendDocument' => function (array $peer, array $inputFile, string $caption) use ($scope): int {
                $updates = $scope->sendMedia($peer, [
                    '_' => 'inputMediaUploadedDocument',
                    'file' => $inputFile,
                    'mime_type' => 'application/octet-stream',
                    'attributes' => [
                        ['_' => 'documentAttributeFilename', 'file_name' => (string) ($inputFile['name'] ?? $caption)],
                    ],
                    'force_file' => true,
                ], $caption);

                return self::sentMessageId($scope, $peer, $updates);
            },
            'sendText' => fn (array $peer, string $text): int => self::sentMessageId(
                $scope,
                $peer,
                $scope->sendMessage($peer, $text)
            ),
            'findMessagesByName' => fn (array $peer, string $namePrefix, int $limit): array => self::searchMessagesByName($scope, $peer, $namePrefix, $limit),
            'listHistoryPage' => fn (array $peer, int $limit, int $offsetId): array => self::historyPageEntries($scope, $peer, $limit, $offsetId),
            'deleteMessages' => fn (array $peer, array $ids): array => $scope->deleteMessages($ids, true),
        ];
    }

    public function putChunk(string $hash, string $bytes): string
    {
        $inputFile = ($this->api['uploadBytes'])($hash, $bytes);
        if (! is_array($inputFile)) {
            throw new RuntimeException("uploadBytes did not return an inputFile for chunk {$hash}");
        }

        $msgId = ($this->api['sendDocument'])($this->inputPeer(), $inputFile, self::CHUNK_CAPTION);

        return (string) $msgId;
    }

    /**
     * Fetch a chunk BY ITS PLAINTEXT CONTENT ADDRESS.
     *
     * The document on the wire is named by its CIPHERTEXT hash (see
     * putChunk), so a manifest written by BackupRunner carries a
     * `wire_chunks` map (ciphertext sha256 → plaintext sha256) and the
     * plaintext address is resolved through it. A legacy manifest with no
     * such map falls back to treating the address as the wire name, which
     * is exactly how those chunks were uploaded.
     *
     * @throws RuntimeException when the chunk is not in the channel
     */
    public function getChunk(string $hash): string
    {
        $wire = $this->wireChunkMap()[$hash] ?? $hash;

        foreach ($this->findEntries($wire) as $entry) {
            if (($entry['name'] ?? null) === $wire) {
                $bytes = ($entry['fetch'])($wire);

                return is_string($bytes) ? $bytes : throw new RuntimeException("chunk {$hash} fetch did not return bytes");
            }
        }

        throw new RuntimeException("chunk {$hash} not found in channel {$this->setId}");
    }

    /**
     * The wire (ciphertext) document names for the given PLAINTEXT content
     * addresses, in the same order. Duck-typed by Pruner so the chunk GC
     * keeps documents by the name they actually have on the channel.
     *
     * @param  list<mixed>  $plainHashes  manifest data: entries are checked, not assumed
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

    public function putManifest(string $json): string
    {
        // Resolve the channel first (so channelAccessHash is validated),
        // then seal the manifest: the on-channel text is marker +
        // base64(XChaCha20-Poly1305 blob) — the plaintext JSON with its
        // paths and salt never reaches the channel. A fresh ciphertext
        // header per run means the manifest posts carry nothing stable to
        // correlate across backups.
        $peer = $this->inputPeer();
        $sealed = VaultCrypto::encryptChunk($this->manifestKey(), $json);
        $text = self::MANIFEST_MARKER.base64_encode($sealed);
        $msgId = ($this->api['sendText'])($peer, $text);
        $this->wireChunkMap = null; // the next manifest rewrites the address map

        return (string) $msgId;
    }

    /**
     * The manifest with the HIGHEST GENERATION wins — not the highest
     * message id.
     *
     * "Highest message id wins" made a rollback undetectable: an attacker
     * who can post in the channel (or a stale client) could post a forged
     * EMPTY manifest and, being newest, become the truth. The generation
     * counter lives INSIDE the sealed blob, so it cannot be forged without
     * the passphrase, and it is compared across every manifest on the
     * channel — a manifest that rolls the set back to an older generation
     * loses to the newer one regardless of which was posted last. A
     * generation this instance has already accepted and now sees a lower
     * one is refused outright.
     */
    public function getLatestManifest(): ?array
    {
        $unreadable = [];
        $read = $this->readManifests($this->findEntries(self::MANIFEST_MARKER), $unreadable);

        $best = $read[0]['data'] ?? null;
        if ($best === null) {
            return null;
        }

        $bestGeneration = $read[0]['generation'];

        if ($bestGeneration < $this->highestGeneration) {
            throw new RuntimeException(
                "manifest generation rollback refused for set {$this->setId}: the channel's newest-generation manifest is "
                ."generation {$bestGeneration} but generation {$this->highestGeneration} was already accepted",
            );
        }
        $this->highestGeneration = $bestGeneration;

        $this->wireChunkMap = $this->parseWireChunkMap($best);

        return $best;
    }

    /**
     * The backup history, newest GENERATION first (VaultInterface).
     *
     * Manifest discovery uses the UNCAPPED history walk rather than
     * findEntries(), because findMessagesByName is capped at SEARCH_LIMIT
     * and a set with more than ~100 messages would report a truncated —
     * and silently hole-ridden — history. The same single walk builds the
     * present-document name set the `restorable` check compares against, so
     * the cost is one pagination, not one per generation.
     *
     * @param  list<int>|null  $unreadableIds
     * @param-out list<int>  $unreadableIds
     * @return list<array{generation: int, created: string, files: int, chunks: int, bytes: int, restorable: bool, missing_chunks: int}>
     */
    public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
    {
        $limit = max(1, $limit);

        $inventory = $this->listAllEntries();

        $present = [];
        foreach ($inventory as $entry) {
            $name = $entry['name'] ?? null;
            if (is_string($name)) {
                $present[$name] = true;
            }
        }

        $unreadable = [];
        $read = $this->readManifests($inventory, $unreadable);
        $unreadableIds = $unreadable;

        $rows = [];
        foreach ($read as $manifest) {
            $rows[] = self::historyRow($manifest, $present);
            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * THE ONE place manifests are read off the wire: filter to the
     * marker-prefixed posts, open each through openManifest(), and rank
     * them by the SEALED generation.
     *
     * Message order is not generation order — a rollback, a repost, or a
     * restored channel make the two diverge — so nothing here may sort by
     * message id. Ties on generation break on the newest post.
     *
     * An unreadable post is SKIPPED and its id collected into
     * $unreadableIds, with ONE exception: the newest manifest post, which
     * fails loud. That post IS the current state, so an unreadable one is
     * a real fault rather than junk (another set's leftovers, a
     * half-deleted row) and must not be papered over by promoting an older
     * generation to "latest".
     *
     * @param  list<array<string, mixed>>  $entries  vault entries (any order)
     * @param  list<int>  $unreadableIds
     * @return list<array{data: array<string, mixed>, generation: int}> newest generation first
     */
    private function readManifests(array $entries, array &$unreadableIds): array
    {
        $posts = [];
        $newestId = 0;
        foreach ($entries as $entry) {
            $name = $entry['name'] ?? null;
            if (! is_string($name) || ! str_starts_with($name, self::MANIFEST_MARKER)) {
                continue; // decoys carrying the marker mid-string are skipped
            }
            $id = (int) ($entry['id'] ?? 0);
            $posts[] = ['id' => $id, 'name' => $name];
            $newestId = max($newestId, $id);
        }

        $unreadableIds = [];
        $read = [];
        foreach ($posts as $post) {
            try {
                $decoded = $this->openManifest($post['name']);
            } catch (RuntimeException $e) {
                if ($post['id'] === $newestId) {
                    throw $e;
                }
                $unreadableIds[] = $post['id'];

                continue;
            }

            $read[] = ['data' => $decoded, 'generation' => self::generationOf($decoded), 'id' => $post['id']];
        }

        // ORDER BY GENERATION, NOT BY MESSAGE ID: the counter is inside the
        // sealed body (so it cannot be forged without the passphrase) while
        // a message id can be reordered by a rollback or a repost.
        usort($read, static function (array $a, array $b): int {
            return $b['generation'] <=> $a['generation'] ?: $b['id'] <=> $a['id'];
        });

        return $read;
    }

    /** A manifest's monotonic generation; a legacy manifest without one is generation 0. */
    private static function generationOf(array $manifest): int
    {
        return is_int($manifest['generation'] ?? null) ? $manifest['generation'] : 0;
    }

    /**
     * One history row: the generation's OWN file/chunk/byte counts, plus
     * whether it can still be restored.
     *
     * The manifest carries its complete chunk address list (`chunk_hashes`
     * plus the ciphertext `wire_chunks` book), so restorability IS decidable
     * from the manifest against the vault's present documents: every
     * referenced chunk name must still be there. A legacy manifest with no
     * `chunk_hashes` references nothing and is trivially restorable.
     *
     * @param  array<string, mixed>  $manifest
     * @param  array<string, true>  $present  present vault entry names
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

        // plaintext address -> the ciphertext name the document actually has
        // (a manifest written before ciphertext addressing has no book, and
        // there the two are the same string — getChunk()'s own rule).
        $wireNames = self::invertWireChunks($manifest['wire_chunks'] ?? null);

        $missing = 0;
        foreach ($hashes as $hash) {
            if (! isset($present[$wireNames[$hash] ?? $hash])) {
                $missing++;
            }
        }

        // A manifest that names NO chunks must not report `restorable: true`.
        //
        // `$missing` counts chunks the manifest claims and the vault does not
        // have, so a manifest with no `chunk_hashes` key at all produced
        // missing=0 → restorable=true → "restorable=yes chunks=0" on a
        // generation that references nothing at all. The flag is a CLAIM ABOUT
        // THIS GENERATION, and a claim with no evidence behind it is not
        // evidence — an operator reading the history would be told the worst
        // generation is fine precisely because it is the emptiest.
        //
        // A legitimately empty object has no chunks to be missing, so the
        // honest test is: restorable only when the manifest actually declared
        // chunks, and every one of them is present.
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
     * Seal-open one marker payload: base64 → AEAD → JSON. Throws
     * RuntimeException for a non-base64 payload, a payload sealed under
     * another key, or non-JSON plaintext.
     */
    private function openManifest(string $name): array
    {
        $sealed = base64_decode(substr($name, strlen(self::MANIFEST_MARKER)), true);
        if ($sealed === false) {
            throw new RuntimeException('latest manifest payload is not valid base64');
        }
        $json = VaultCrypto::decryptChunk($this->manifestKey(), $sealed);
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('latest manifest payload is not valid JSON');
        }

        return $decoded;
    }

    /**
     * The manifest's plaintext → ciphertext chunk address map, inverted
     * for getChunk(). Only string→string pairs are trusted; a hostile
     * manifest can mislabel a document but it cannot forge the AEAD that
     * carries the map, and a mislabelled document still fails its hash.
     *
     * @param  array<string, mixed>  $manifest
     * @return array<string, string>
     */
    private function parseWireChunkMap(array $manifest): array
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
     * The (cached) plaintext → ciphertext chunk address map of the latest
     * manifest. A vault with no manifest key throws — there is no manifest
     * to read, and chunks are addressed through it.
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

    /**
     * List vault entries by name prefix; '' (the prune GC walk) returns
     * every entry. Entries are the api-level rows reduced to id + name.
     *
     * @return list<array<string, mixed>>
     */
    public function findMessagesByName(string $namePrefix): array
    {
        $entries = [];
        foreach ($this->findEntries($namePrefix) as $entry) {
            $entries[] = ['id' => (string) ($entry['id'] ?? 0), 'name' => (string) ($entry['name'] ?? '')];
        }

        return $entries;
    }

    /**
     * UNcapped channel inventory (night quality fix): findEntries caps
     * at SEARCH_LIMIT=100, so the prune GC riding findMessagesByName('')
     * never saw orphans older than the newest ~100 messages — at ≥50 MB
     * sets those survived every prune forever. This walk instead pages
     * messages.getHistory (offset_id = oldest id of the last page, LIST_
     * PAGE_SIZE rows) until an empty page. Two pathological aborts THROW
     * for manual review rather than silently truncate: a stream larger
     * than LIST_HARD_CAP, and pagination that makes no forward progress.
     *
     * @return list<array<string, mixed>> same shape as findMessagesByName(): {id: string, name: string}, newest first
     */
    public function listAllEntries(): array
    {
        $entries = [];
        $seen = [];
        $fetched = 0;
        $offsetId = 0;
        while (true) {
            $rows = ($this->api['listHistoryPage'])($this->inputPeer(), self::LIST_PAGE_SIZE, $offsetId);
            $rows = is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
            if ($rows === []) {
                break; // walked past the oldest message: complete inventory
            }

            $fetched += count($rows);
            if ($fetched > self::LIST_HARD_CAP) {
                throw new RuntimeException('vault channel '.$this->setId.' exceeds the '.self::LIST_HARD_CAP.'-entry listing hard cap; aborting the prune walk for manual review');
            }

            $oldest = 0;
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                $oldest = $oldest === 0 ? $id : min($oldest, $id);
                if (isset($seen[$id])) {
                    continue; // defensive: a page repeating an id must not duplicate the entry
                }
                $seen[$id] = true;
                $entries[] = ['id' => (string) $id, 'name' => (string) ($row['name'] ?? '')];
            }

            if ($oldest <= 0 || ($offsetId !== 0 && $oldest >= $offsetId)) {
                throw new RuntimeException('vault channel '.$this->setId.' history pagination made no forward progress at offset_id '.$offsetId.'; aborting for manual review');
            }
            $offsetId = $oldest;
        }

        return $entries;
    }

    /**
     * Delete the entry stored under $name (messages.deleteMessages with
     * revoke on every id carrying that exact name — normally one).
     * Idempotent: an absent name deletes nothing.
     */
    public function delete(string $name): void
    {
        $ids = [];
        foreach ($this->findEntries($name) as $entry) {
            if (($entry['name'] ?? null) === $name) {
                $ids[] = (int) $entry['id'];
            }
        }

        if ($ids !== []) {
            ($this->api['deleteMessages'])($this->inputPeer(), $ids);
        }
    }

    /** Lazily resolved (find-or-create) and cached channel id for this set. */
    public function channelId(): int
    {
        if ($this->channelId !== null) {
            return $this->channelId;
        }

        $title = self::channelTitle($this->setId);
        $found = ($this->api['findChannel'])($title);
        $ref = is_array($found)
            ? ['id' => (int) $found['id'], 'access_hash' => (int) ($found['access_hash'] ?? 0)]
            : ($this->api['createChannel'])($title, self::CHANNEL_ABOUT);
        $this->channelId = (int) $ref['id'];
        $this->channelAccessHash = (int) ($ref['access_hash'] ?? 0);

        // An unresolved access hash is an ERROR, not a degraded default. It
        // used to flow straight into the manifest key derivation, where 0
        // produced a globally constant, publicly computable key (H5); a peer
        // that cannot be addressed is a broken channel, and pretending
        // otherwise hides it.
        if ($this->channelAccessHash === 0) {
            $resolvedId = $this->channelId;
            $this->channelId = null;
            $this->channelAccessHash = 0;
            throw new RuntimeException(
                "backup channel '{$title}' resolved to id {$resolvedId} with NO access_hash; "
                .'refusing to address it (and refusing to derive any key from it)',
            );
        }

        return $this->channelId;
    }

    /** MTProto inputPeerChannel for the vault channel. @return array<string, mixed> */
    public function inputPeer(): array
    {
        $this->channelId();

        return ['_' => 'inputPeerChannel', 'channel_id' => $this->channelId, 'access_hash' => $this->channelAccessHash];
    }

    /**
     * 32-byte AEAD key for sealing manifests, derived from the set
     * PASSPHRASE (VaultCrypto::deriveManifestKey).
     *
     * WHY NOT THE ACCESS HASH. This used to be
     * `sodium_crypto_generichash(CONTEXT . $channelAccessHash)` — with the
     * KEY ARGUMENT DEFAULTED TO "", i.e. an UNKEYED public hash function,
     * so the AEAD key was BLAKE2b("teleframe-backup:vault-manifest:v1:"
     * . access_hash): computable by anyone. Telegram hands that access_hash
     * to every account that can SEE the channel, and this very class reads
     * it at scopeApi()'s findChannel — the manifest's own audience already
     * has the value protecting the manifest (which carries the file tree,
     * the per-file chunk order and the Argon2id salt: a fully indexed
     * plaintext oracle and an offline KDF target for anyone who can list
     * the channel). An unresolved access hash made it worse: a
     * hardcoded, globally constant key. A secret must come from something
     * the adversary does NOT have, so it comes from the passphrase, and a
     * missing passphrase throws instead of degrading to a public key.
     */
    private function manifestKey(): string
    {
        if ($this->manifestPassphrase === null || $this->manifestPassphrase === '') {
            throw new RuntimeException(
                "backup set '{$this->setId}' has no manifest passphrase: the manifest index cannot be sealed or read "
                .'without a secret (construct the vault with one — TelegramVault::forScope($scope, $setId, $passphrase))',
            );
        }

        return VaultCrypto::deriveManifestKey($this->setId, $this->manifestPassphrase);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findEntries(string $namePrefix): array
    {
        $entries = ($this->api['findMessagesByName'])($this->inputPeer(), $namePrefix, self::SEARCH_LIMIT);

        return is_array($entries) ? $entries : [];
    }

    /**
     * upload.saveFilePart / saveBigFilePart loop from raw bytes (Storage-
     * Media part math: 512 KB parts, big-file switch at 10 MB), returning
     * a TL inputFile named $name.
     *
     * @return array<string, mixed>
     */
    private static function uploadBytes(UserAccountScope $scope, string $name, string $bytes): array
    {
        $size = strlen($bytes);
        $totalParts = max(1, (int) ceil($size / self::PART_SIZE));
        $isBig = $size > self::BIG_FILE_THRESHOLD;
        $fileId = random_int(1, PHP_INT_MAX);

        for ($part = 0; $part < $totalParts; $part++) {
            $params = [
                'file_id' => $fileId,
                'file_part' => $part,
                'bytes' => substr($bytes, $part * self::PART_SIZE, self::PART_SIZE),
            ];
            if ($isBig) {
                // The key is `file_total_parts`, NOT `total_parts`:
                // upload.saveBigFilePart#de7b673d declares
                // `file_id:long file_part:int file_total_parts:int bytes:string`
                // (vendored spec src/Schema/schema/sources/api.tl:2758, mirrored
                // at src/Core/MTProto/TL/TLRegistry.php:135). TLEncoder looks
                // each declared field up BY NAME, so a `total_parts` key is
                // silently dropped and the encode then throws
                // "missing field 'file_total_parts'" — every backup over
                // 10 MB would fail here. Note the order of the keys in $params
                // is irrelevant: the encoder re-serialises in declared order.
                $params['file_total_parts'] = $totalParts;
                $scope->call('upload.saveBigFilePart', $params);
            } else {
                $scope->call('upload.saveFilePart', $params);
            }
        }

        return [
            '_' => 'inputFile',
            'id' => $fileId,
            'parts' => $totalParts,
            'name' => $name,
            'md5_checksum' => md5($bytes),
        ];
    }

    /**
     * Pull the sent message id out of a messages.send{Message,Media}
     * updates reply; updatesTooLong-shaped replies fall back to the
     * newest history row (channels echo channel-post ids there).
     */
    private static function sentMessageId(UserAccountScope $scope, array $peer, array $updates): int
    {
        $messages = $updates['messages'] ?? null;
        $first = is_array($messages) ? ($messages[0] ?? null) : null;
        $id = is_array($first) ? ($first['id'] ?? null) : ($updates['id'] ?? null);
        if (is_numeric($id)) {
            return (int) $id;
        }

        $history = $scope->getHistory($peer, 1);
        $rows = $history['messages'] ?? [];
        $newest = is_array($rows) ? ($rows[0] ?? null) : null;
        if (is_array($newest) && is_numeric($newest['id'] ?? null)) {
            return (int) $newest['id'];
        }

        throw new RuntimeException('cannot resolve sent message id in channel '.($peer['channel_id'] ?? '?'));
    }

    /**
     * messages.search by $namePrefix mapped to vault entries: name is the
     * document file name for documents (the message text otherwise) and
     * fetch(name) streams the document back via the upload.getFile loop.
     *
     * @return list<array<string, mixed>>
     */
    private static function searchMessagesByName(UserAccountScope $scope, array $peer, string $namePrefix, int $limit): array
    {
        $rows = self::mergeRowsById(
            self::resultRows($scope->searchMessages($peer, $namePrefix, $limit)),
            self::resultRows($scope->getHistory($peer, $limit)),
        );

        $entries = [];
        foreach ($rows as $message) {
            $name = self::messageName($message);
            if ($name === null) {
                continue;
            }
            $entries[] = [
                'id' => (int) ($message['id'] ?? 0),
                'name' => $name,
                'fetch' => fn (string $fetchName): string => self::downloadDocument($scope, $message),
            ];
        }

        return $entries;
    }

    /** Message rows of a search/history reply; non-array rows dropped. @return list<array<string, mixed>> */
    private static function resultRows(array $result): array
    {
        $rows = $result['messages'] ?? [];

        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    /**
     * One messages.getHistory page strictly below $offsetId (0 = newest)
     * mapped to api-level vault entries — the raw material the uncapped
     * listAllEntries() walk pages through.
     *
     * @return list<array<string, mixed>>
     */
    private static function historyPageEntries(UserAccountScope $scope, array $peer, int $limit, int $offsetId): array
    {
        $entries = [];
        foreach (self::resultRows($scope->getHistory($peer, $limit, $offsetId)) as $message) {
            $name = self::messageName($message);
            if ($name === null) {
                continue;
            }
            $entries[] = [
                'id' => (int) ($message['id'] ?? 0),
                'name' => $name,
                'fetch' => fn (string $fetchName): string => self::downloadDocument($scope, $message),
            ];
        }

        return $entries;
    }

    /**
     * Search rows first, realtime history rows filling gaps by message id:
     * the channel text index ingests fresh posts minutes late, so search
     * alone must not be the find loop's only source.
     *
     * @param  list<array<string, mixed>>  $searchRows
     * @param  list<array<string, mixed>>  $historyRows
     * @return list<array<string, mixed>>
     */
    private static function mergeRowsById(array $searchRows, array $historyRows): array
    {
        $merged = [];
        foreach (array_merge($searchRows, $historyRows) as $row) {
            $merged[(int) ($row['id'] ?? 0)] = $row;
        }

        return array_values($merged);
    }

    /** Document file name, else the message text; null when neither applies. */
    private static function messageName(array $message): ?string
    {
        $document = $message['media']['document'] ?? null;
        if (is_array($document)) {
            $attributes = $document['attributes'] ?? [];
            if (is_array($attributes)) {
                foreach ($attributes as $attribute) {
                    if (is_array($attribute)
                        && ($attribute['_'] ?? '') === 'documentAttributeFilename'
                        && is_string($attribute['file_name'] ?? null)
                        && $attribute['file_name'] !== ''
                    ) {
                        return $attribute['file_name'];
                    }
                }
            }
        }

        $text = $message['message'] ?? null;

        return is_string($text) && $text !== '' ? $text : null;
    }

    /** upload.getFile part loop over a message's document. */
    private static function downloadDocument(UserAccountScope $scope, array $message): string
    {
        $document = $message['media']['document'] ?? null;
        if (! is_array($document) || ($document['_'] ?? '') !== 'document') {
            throw new RuntimeException('message carries no downloadable document');
        }

        $location = [
            '_' => 'inputDocumentFileLocation',
            'id' => $document['id'] ?? 0,
            'access_hash' => $document['access_hash'] ?? 0,
            'file_reference' => $document['file_reference'] ?? '',
            'thumb_size' => '',
        ];

        $bytes = '';
        $offset = 0;
        while (true) {
            $file = $scope->call('upload.getFile', [
                'location' => $location,
                'offset' => $offset,
                'limit' => self::PART_SIZE,
            ]);
            $part = is_string($file['bytes'] ?? null) ? $file['bytes'] : '';
            if ($part === '') {
                break;
            }
            $bytes .= $part;
            $offset += strlen($part);
            if (strlen($part) < self::PART_SIZE) {
                break;
            }
        }

        return $bytes;
    }
}
