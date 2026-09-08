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
 * are force_file documents NAMED by their plaintext content hash (caption
 * too), manifests are text messages carrying MANIFEST_MARKER + base64 JSON —
 * latest manifest wins, chunk lookup is exact-name, marker detection is
 * str_starts_with/substr only (src/ is regex-free by policy).
 *
 * Every teleframe call lives behind an injectable call map so tests run
 * offline; scopeApi()/forScope() build that map from a real UserAccount-
 * Scope/BotAccountScope using teleframe's actual surfaces: the
 * upload.saveFilePart/saveBigFilePart part loop (StorageMedia part math),
 * messages.sendMedia with inputMediaUploadedDocument, messages.sendMes-
 * sage, messages.search, messages.deleteMessages and the upload.getFile
 * download loop.
 */
final class TelegramVault implements VaultInterface
{
    public const CHANNEL_PREFIX = 'teleframe-backup:';
    public const MANIFEST_MARKER = 'TBMANIFEST1:';
    public const CHANNEL_ABOUT = 'teleframe encrypted backup vault';

    private const SEARCH_LIMIT = 100;
    private const LIST_PAGE_SIZE = 100; // getHistory page size of the uncapped prune walk
    private const LIST_HARD_CAP = 10000; // pathological-stream abort: throw, never truncate
    private const PART_SIZE = 524288; // 512 KB standard MTProto part size
    private const BIG_FILE_THRESHOLD = 10485760; // > 10 MB switches to saveBigFilePart

    private const API_KEYS = ['findChannel', 'createChannel', 'uploadBytes', 'sendDocument', 'sendText', 'findMessagesByName', 'deleteMessages', 'listHistoryPage'];

    private ?int $channelId = null;

    private int $channelAccessHash = 0;

    /**
     * @param array<string, callable> $api injectable teleframe call map; see scopeApi()
     *
     * @throws InvalidArgumentException when a required callable is missing
     */
    public function __construct(private readonly string $setId, private readonly array $api)
    {
        $missing = [];
        foreach (self::API_KEYS as $key) {
            if (!array_key_exists($key, $this->api)) {
                $missing[] = $key;
            }
        }
        if ($missing !== []) {
            throw new InvalidArgumentException('vault api map is missing callable(s): ' . implode(', ', $missing));
        }
    }

    /** Channel title for a backup set: CHANNEL_PREFIX + setId. */
    public static function channelTitle(string $setId): string
    {
        return self::CHANNEL_PREFIX . $setId;
    }

    /** Vault over a real scope: the default call map below. */
    public static function forScope(UserAccountScope $scope, string $setId): self
    {
        return new self($setId, self::scopeApi($scope));
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
     * @param UserAccountScope $scope a BotAccountScope passes as-is (subclass)
     * @return array<string, callable>
     */
    public static function scopeApi(UserAccountScope $scope): array
    {
        return [
            'findChannel' => function (string $title) use ($scope): ?array {
                $dialogs = $scope->getDialogs(100);
                $chats = $dialogs['chats'] ?? [];
                if (!is_array($chats)) {
                    return null;
                }
                foreach ($chats as $chat) {
                    if (!is_array($chat) || ($chat['_'] ?? '') !== 'channel') {
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
        if (!is_array($inputFile)) {
            throw new RuntimeException("uploadBytes did not return an inputFile for chunk {$hash}");
        }

        $msgId = ($this->api['sendDocument'])($this->inputPeer(), $inputFile, $hash);

        return (string) $msgId;
    }

    public function getChunk(string $hash): string
    {
        foreach ($this->findEntries($hash) as $entry) {
            if (($entry['name'] ?? null) === $hash) {
                $bytes = ($entry['fetch'])($hash);

                return is_string($bytes) ? $bytes : throw new RuntimeException("chunk {$hash} fetch did not return bytes");
            }
        }

        throw new RuntimeException("chunk {$hash} not found in channel {$this->setId}");
    }

    public function putManifest(string $json): string
    {
        $text = self::MANIFEST_MARKER . base64_encode($json);
        $msgId = ($this->api['sendText'])($this->inputPeer(), $text);

        return (string) $msgId;
    }

    public function getLatestManifest(): ?array
    {
        $latestId = 0;
        $latestName = null;
        foreach ($this->findEntries(self::MANIFEST_MARKER) as $entry) {
            $name = $entry['name'] ?? null;
            if (!is_string($name) || !str_starts_with($name, self::MANIFEST_MARKER)) {
                continue; // decoys carrying the marker mid-string are skipped
            }
            if ((int) $entry['id'] > $latestId) {
                $latestId = (int) $entry['id'];
                $latestName = $name;
            }
        }

        if ($latestName === null) {
            return null;
        }

        $payload = base64_decode(substr($latestName, strlen(self::MANIFEST_MARKER)), true);
        if ($payload === false) {
            throw new RuntimeException('latest manifest payload is not valid base64');
        }
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('latest manifest payload is not valid JSON');
        }

        return $decoded;
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
                throw new RuntimeException('vault channel ' . $this->setId . ' exceeds the ' . self::LIST_HARD_CAP . '-entry listing hard cap; aborting the prune walk for manual review');
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
                throw new RuntimeException('vault channel ' . $this->setId . ' history pagination made no forward progress at offset_id ' . $offsetId . '; aborting for manual review');
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

        return $this->channelId;
    }

    /** MTProto inputPeerChannel for the vault channel. @return array<string, mixed> */
    public function inputPeer(): array
    {
        $this->channelId();

        return ['_' => 'inputPeerChannel', 'channel_id' => $this->channelId, 'access_hash' => $this->channelAccessHash];
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
                $params['total_parts'] = $totalParts;
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

        throw new RuntimeException('cannot resolve sent message id in channel ' . ($peer['channel_id'] ?? '?'));
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
     * @param list<array<string, mixed>> $searchRows
     * @param list<array<string, mixed>> $historyRows
     *
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
        if (!is_array($document) || ($document['_'] ?? '') !== 'document') {
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
