<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use RuntimeException;

/**
 * In-memory VaultInterface fake (ships as a tool per plan Phase 4): a
 * flat message stack — chunk documents named by hash (latest write wins
 * per hash) plus manifest text messages whose top is the latest —
 * mirroring what a channel-backed vault returns. Message ids are
 * synthetic incrementing strings. findMessagesByName('') lists the
 * whole stack and delete() drops every exact-name entry, so the prune
 * GC exercises the same walk a Telegram vault performs.
 */
final class InMemoryVault implements VaultInterface
{
    /** The same manifest marker TelegramVault writes (VaultInterface's wire contract). */
    public const MANIFEST_MARKER = TelegramVault::MANIFEST_MARKER;

    /** @var list<array{id: int, name: string, is_manifest: bool}> message stack, oldest first */
    private array $messages = [];

    /** @var array<string, string> chunk name → ciphertext bytes (latest write wins) */
    private array $chunks = [];

    /** @var array<string, string> plaintext sha256 → the chunk name it is stored under */
    private array $wireChunkMap = [];

    private int $nextMsgId = 1;

    public function putChunk(string $hash, string $bytes): string
    {
        $this->messages[] = ['id' => $this->nextMsgId, 'name' => $hash, 'is_manifest' => false];
        $this->chunks[$hash] = $bytes;

        return (string) $this->nextMsgId++;
    }

    /**
     * Accept the manifest's ciphertext → plaintext address book, exactly as
     * the transport-backed vaults read it out of their own manifest. Called
     * by putManifest() so a later getChunk($plaintextHash) resolves the way
     * it does on a real vault (a document is named by its CIPHERTEXT hash,
     * never by the plaintext address the manifest lists).
     *
     * @param  array<string, mixed>  $manifest
     */
    public function putChunkAddresses(array $manifest): void
    {
        $wire = $manifest['wire_chunks'] ?? null;
        if (! is_array($wire)) {
            return;
        }

        foreach ($wire as $wireHash => $plainHash) {
            if (is_string($wireHash) && is_string($plainHash)) {
                $this->wireChunkMap[$plainHash] = $wireHash;
            }
        }
    }

    /**
     * The wire (ciphertext) document names for the given PLAINTEXT content
     * addresses — the Pruner duck-type (TelegramVault has the same). The
     * address book is refreshed from the latest manifest first, exactly as
     * a reader would; with no manifest there is no book and the plaintext
     * addresses are returned unchanged, which is correct for a vault whose
     * chunks were stored under those names.
     *
     * @param  list<mixed>  $plainHashes
     * @return list<string>
     */
    public function wireNamesFor(array $plainHashes): array
    {
        $this->getLatestManifest();

        $names = [];
        foreach ($plainHashes as $hash) {
            if (is_string($hash)) {
                $names[] = $this->wireChunkMap[$hash] ?? $hash;
            }
        }

        return $names;
    }

    public function getChunk(string $hash): string
    {
        $name = $this->wireChunkMap[$hash] ?? $hash;
        if (! array_key_exists($name, $this->chunks)) {
            throw new RuntimeException("chunk {$hash} not stored in this vault");
        }

        return $this->chunks[$name];
    }

    public function putManifest(string $json): string
    {
        $this->messages[] = ['id' => $this->nextMsgId, 'name' => self::MANIFEST_MARKER.$json, 'is_manifest' => true];

        return (string) $this->nextMsgId++;
    }

    public function getLatestManifest(): ?array
    {
        $unreadable = [];
        $read = $this->readManifests($unreadable);

        $latest = $read[0]['data'] ?? null;
        if ($latest === null) {
            return null;
        }

        $this->putChunkAddresses($latest);

        return $latest;
    }

    /**
     * The backup history, newest GENERATION first (VaultInterface). Walks
     * the same message stack getLatestManifest() reads, and computes
     * `restorable` against the stack's present chunk documents — which for
     * this fake IS a complete listing (no cap, no index lag).
     *
     * @param  list<int>|null  $unreadableIds
     *
     * @param-out list<int>  $unreadableIds
     *
     * @return list<array{generation: int, created: string, files: int, chunks: int, bytes: int, restorable: bool, missing_chunks: int}>
     */
    public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
    {
        $limit = max(1, $limit);

        $present = [];
        foreach ($this->messages as $message) {
            $present[$message['name']] = true;
        }

        $unreadable = [];
        $read = $this->readManifests($unreadable);
        $unreadableIds = $unreadable;

        $rows = [];
        foreach ($read as $manifest) {
            $rows[] = $this->historyRow($manifest['data'], $present);
            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * The newest $generations generations' PLAINTEXT chunk addresses
     * (VaultInterface), so a prune can build a retention keep-set that is
     * wider than one generation. Same walk, same order and same
     * unreadable-post rule as listManifests() — the addresses are the only
     * thing this adds, and they are what listManifests() withholds.
     *
     * @return list<array{generation: int, created: string, chunk_hashes: list<string>}>
     */
    public function listManifestChunkHashes(int $generations = 2): array
    {
        $limit = max(1, $generations);

        $unreadable = [];
        $read = $this->readManifests($unreadable);

        $rows = [];
        foreach ($read as $manifest) {
            $rows[] = self::chunkHashRow($manifest['data']);
            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * One retention row: the generation's identity and the PLAINTEXT
     * addresses its manifest lists. Non-string entries in `chunk_hashes` are
     * dropped rather than trusted — manifest data is untrusted until its own
     * hash and AEAD say otherwise, and a keep-set built from a forged entry
     * is a keep-set that does not keep.
     *
     * @param  array<string, mixed>  $manifest
     * @return array{generation: int, created: string, chunk_hashes: list<string>}
     */
    private static function chunkHashRow(array $manifest): array
    {
        $hashes = $manifest['chunk_hashes'] ?? [];
        $hashes = is_array($hashes) ? array_values(array_filter($hashes, 'is_string')) : [];

        return [
            'generation' => is_int($manifest['generation'] ?? null) ? $manifest['generation'] : 0,
            'created' => is_string($manifest['created'] ?? null) ? $manifest['created'] : 'unknown',
            'chunk_hashes' => $hashes,
        ];
    }

    /**
     * THE ONE place manifests are read: order by the SEALED generation, not
     * by message id — a rollback or a repost makes the two diverge, and a
     * history in message order would present an old generation as newest.
     * Ties break on the newest post (TelegramVault's rule).
     *
     * A post whose body is not JSON is SKIPPED, with its id collected into
     * $unreadableIds, unless it is the NEWEST manifest post: that one is
     * the current state, so an unreadable current state must not be
     * papered over by promoting an older generation to "latest".
     *
     * @param  list<int>  $unreadableIds
     * @return list<array{data: array<string, mixed>, generation: int, id: int}>
     */
    private function readManifests(array &$unreadableIds): array
    {
        $newestId = 0;
        foreach ($this->messages as $message) {
            if ($message['is_manifest']) {
                $newestId = max($newestId, $message['id']);
            }
        }

        $unreadableIds = [];
        $read = [];
        foreach ($this->messages as $message) {
            if (! $message['is_manifest']) {
                continue;
            }
            $decoded = json_decode(substr($message['name'], strlen(self::MANIFEST_MARKER)), true);
            if (! is_array($decoded)) {
                if ($message['id'] === $newestId) {
                    throw new RuntimeException('latest manifest payload is not valid JSON');
                }
                $unreadableIds[] = $message['id'];

                continue;
            }

            $read[] = [
                'data' => $decoded,
                'generation' => is_int($decoded['generation'] ?? null) ? $decoded['generation'] : 0,
                'id' => $message['id'],
            ];
        }

        usort($read, static function (array $a, array $b): int {
            return $b['generation'] <=> $a['generation'] ?: $b['id'] <=> $a['id'];
        });

        return $read;
    }

    /**
     * One history row: the generation's OWN counts plus whether it can
     * still be restored. The manifest carries its complete chunk address
     * list, so restorability is decidable against the present documents.
     *
     * @param  array<string, mixed>  $manifest
     * @param  array<string, true>  $present
     * @return array{generation: int, created: string, files: int, chunks: int, bytes: int, restorable: bool, missing_chunks: int}
     */
    private function historyRow(array $manifest, array $present): array
    {
        $files = $manifest['files'] ?? [];
        $files = is_array($files) ? array_values(array_filter($files, 'is_array')) : [];

        $bytes = 0;
        foreach ($files as $file) {
            $bytes += is_int($file['size'] ?? null) ? $file['size'] : 0;
        }

        $hashes = $manifest['chunk_hashes'] ?? [];
        $hashes = is_array($hashes) ? array_values(array_filter($hashes, 'is_string')) : [];

        $wireNames = [];
        $wire = $manifest['wire_chunks'] ?? null;
        if (is_array($wire)) {
            foreach ($wire as $wireHash => $plainHash) {
                if (is_string($wireHash) && is_string($plainHash)) {
                    $wireNames[$plainHash] = $wireHash;
                }
            }
        }

        $missing = 0;
        foreach ($hashes as $hash) {
            if (! isset($present[$wireNames[$hash] ?? $hash])) {
                $missing++;
            }
        }

        return [
            'generation' => is_int($manifest['generation'] ?? null) ? $manifest['generation'] : 0,
            'created' => is_string($manifest['created'] ?? null) ? $manifest['created'] : 'unknown',
            'files' => count($files),
            'chunks' => count($hashes),
            'bytes' => $bytes,
            'restorable' => $missing === 0,
            'missing_chunks' => $missing,
        ];
    }

    public function findMessagesByName(string $namePrefix): array
    {
        $entries = [];
        foreach ($this->messages as $message) {
            if (str_starts_with($message['name'], $namePrefix)) {
                $entries[] = ['id' => (string) $message['id'], 'name' => $message['name']];
            }
        }

        return $entries;
    }

    public function delete(string $name): void
    {
        $this->messages = array_values(array_filter(
            $this->messages,
            static fn (array $message): bool => $message['name'] !== $name,
        ));
        unset($this->chunks[$name]);
    }
}
