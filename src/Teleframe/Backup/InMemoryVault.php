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
    /** @var list<array{id: int, name: string, is_manifest: bool}> message stack, oldest first */
    private array $messages = [];

    /** @var array<string, string> chunk hash → ciphertext bytes (latest write wins) */
    private array $chunks = [];

    private int $nextMsgId = 1;

    public function putChunk(string $hash, string $bytes): string
    {
        $this->messages[] = ['id' => $this->nextMsgId, 'name' => $hash, 'is_manifest' => false];
        $this->chunks[$hash] = $bytes;

        return (string) $this->nextMsgId++;
    }

    public function getChunk(string $hash): string
    {
        if (!array_key_exists($hash, $this->chunks)) {
            throw new RuntimeException("chunk {$hash} not stored in this vault");
        }

        return $this->chunks[$hash];
    }

    public function putManifest(string $json): string
    {
        $this->messages[] = ['id' => $this->nextMsgId, 'name' => 'TBMANIFEST1:' . $json, 'is_manifest' => true];

        return (string) $this->nextMsgId++;
    }

    public function getLatestManifest(): ?array
    {
        $latestJson = null;
        foreach ($this->messages as $message) {
            if ($message['is_manifest']) {
                $latestJson = $message['name'];
            }
        }
        if ($latestJson === null) {
            return null;
        }

        $decoded = json_decode(substr($latestJson, strlen('TBMANIFEST1:')), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('latest manifest is not valid JSON');
        }

        return $decoded;
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
