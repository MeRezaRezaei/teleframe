<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;

/**
 * In-memory TelegramStoreChannel api-map double (plan W2): a virtual
 * broadcast channel with monotonically increasing message ids, exact-title
 * dialog scanning, and a message log where every entry is either a document
 * (uploaded part-by-part into the part store, downloaded via a synthetic
 * Telegram document id) or a text post. Search is a name-prefix filter
 * (newest first); history pages by message id.
 *
 * Failure injection: failNextUpload()/failNextDownload() make the next
 * uploadPart/getFile call throw, so the resilient paths inside the channel
 * are exercised offline.
 *
 * PEERS: the virtual wire is PEER-PARTITIONED — every send/search/history/
 * delete is scoped to the peer's channel_id, so a store channel configured
 * with a PeerTarget writes into a different virtual peer than the bucket's
 * own channel and a restore can only find its chunks if the routing is real.
 * `$messages` stays the FLAT aggregate over every peer (so existing
 * assertions still read it as one log); `$peerMessages` is the per-peer
 * partition, and `$sends` records every send with the peer it was addressed
 * to, which is what proves WHERE a document actually went.
 */
final class FakeStoreApi
{
    /** @var list<array{id: int, name: string, doc_id: int|null, bytes: string|null, text: string|null}> */
    public array $messages = [];

    /** @var array<int, list<array{id: int, name: string, doc_id: int|null, bytes: string|null, text: string|null}>> channel_id => its messages */
    public array $peerMessages = [];

    /** @var list<array{kind: string, peer: array<string, mixed>, name: string, id: int}> every send, with the peer addressed */
    public array $sends = [];

    /** @var list<array{peer: array<string, mixed>, query: string}> every searchMessages call, in order */
    public array $searches = [];

    /** @var list<array{peer: array<string, mixed>, ids: list<int>}> every deleteMessages call, in order */
    public array $deletes = [];

    /** @var array<string, array{id: int, access_hash: int}> title => channel */
    public array $channels = [];

    public int $createChannelCalls = 0;

    public int $uploadPartCalls = 0;

    /**
     * Every reference send (Cycle 14): {peer, media, caption}. A row here
     * with uploadPartCalls still at 0 is the proof that publishing an
     * already-stored file never touched the bytes.
     *
     * @var list<array{peer: array<string, mixed>, media: array<string, mixed>, caption: string}>
     */
    public array $referenceSends = [];

    public int $nextDocumentId = 5000;

    /** When true, historyPage keeps returning the same rows (cursor never advances). */
    public bool $nextHistoryStall = false;

    /** @var array<string, string> "scope|fileId|part" => bytes */
    private array $uploads = [];

    /** @var array<int, string> "docId" => bytes (download store) */
    private array $documents = [];

    private ?int $failNextUploadSeconds = null;

    private ?int $failNextDownloadSeconds = null;

    /** @var list<array<string, mixed>> last historyPage reply, for stall injection */
    private array $historyStallRows = [];

    /**
     * The peer that directly-seeded messages (uploadDocument/sendText) belong
     * to. Those helpers take no peer, so a test that needs them in a specific
     * peer sets this; otherwise they land in peer 0 and a search against a
     * peer with no messages of its own still sees them.
     */
    public int $seedPeerId = 0;

    /**
     * Whether a search against a peer with no partition of its own falls back
     * to the seed peer. TRUE keeps the single-peer fixtures working; a
     * MULTI-peer test MUST set it false, because a fallback there would let
     * a search of the target peer see the bucket channel's messages and mask
     * exactly the misrouting these tests exist to catch.
     */
    public bool $seedFallback = true;

    public function __construct(
        private readonly string $scope = 'default',
        private int $nextId = 1,
        private int $nextChannelId = 100,
    ) {}

    /**
     * The message log of one peer, newest last. With $seedFallback, a peer
     * that has no messages of its own falls back to the seed peer so
     * single-peer tests that seed directly (uploadDocument/sendText) keep
     * seeing their fixtures.
     *
     * @return list<array{id: int, name: string, doc_id: int|null, bytes: string|null, text: string|null}>
     */
    public function messagesFor(array $peer): array
    {
        $channelId = (int) ($peer['channel_id'] ?? 0);
        if (isset($this->peerMessages[$channelId])) {
            return $this->peerMessages[$channelId];
        }

        return $this->seedFallback ? ($this->peerMessages[$this->seedPeerId] ?? []) : [];
    }

    public function apiMap(): array
    {
        return [
            'findChannel' => function (string $title): ?array {
                return $this->channels[$title] ?? null;
            },
            'createChannel' => function (string $title, string $about): array {
                $this->createChannelCalls++;
                $this->channels[$title] = ['id' => $this->nextChannelId++, 'access_hash' => 42];

                return $this->channels[$title];
            },
            'uploadPart' => function (int $fileId, int $part, int $totalParts, string $bytes): array {
                $this->uploadPartCalls++;
                if ($this->failNextUploadSeconds !== null) {
                    $seconds = $this->failNextUploadSeconds;
                    $this->failNextUploadSeconds = null;
                    throw new FloodWaitException($seconds, "FLOOD_WAIT_{$seconds}", 420);
                }
                $this->uploads[$this->scope.'|'.$fileId.'|'.$part] = $bytes;

                return ['_' => 'uploaded', 'file_id' => $fileId, 'part' => $part];
            },
            'sendDocument' => function (array $peer, array $inputFile, string $caption): int {
                $key = $this->scope.'|'.($inputFile['id'] ?? 0);
                $bytes = '';
                $part = 0;
                while (isset($this->uploads[$key.'|'.$part])) {
                    $bytes .= $this->uploads[$key.'|'.$part];
                    $part++;
                }
                $name = (string) ($inputFile['name'] ?? $caption);
                $docId = $this->nextDocumentId++;
                $this->documents[$docId] = $bytes;
                $id = $this->nextId++;
                $this->record($peer, $id, $name, $docId, $bytes, null);

                return $id;
            },
            'sendMessage' => function (array $peer, string $text): int {
                $id = $this->nextId++;
                $this->record($peer, $id, self::firstLine($text), null, null, $text);

                return $id;
            },
            // Cycle 14: inputMediaDocument + inputDocument — a NEW message
            // pointing at an existing document, zero uploads. The declared
            // field is `id:InputDocument` (inputMediaDocument#a8763ab5); the
            // legacy `document` key is still read so a caller still on the old
            // shape is served rather than silently resolving doc id 0.
            'sendReference' => function (array $peer, array $media, string $caption): int {
                $this->referenceSends[] = ['peer' => $peer, 'media' => $media, 'caption' => $caption];
                $document = is_array($media['id'] ?? null) ? $media['id'] : [];
                $docId = (int) ($document['id'] ?? $media['document']['id'] ?? 0);
                $bytes = $this->documents[$docId] ?? null;
                $id = $this->nextId++;
                $this->record($peer, $id, $caption !== '' ? self::firstLine($caption) : 'ref-'.$docId, $docId, $bytes, null);

                return $id;
            },
            'searchMessages' => function (array $peer, string $query, int $limit): array {
                $this->searches[] = ['peer' => $peer, 'query' => $query];
                $found = [];
                foreach (array_reverse($this->messagesFor($peer)) as $message) {
                    if ($query !== '' && ! str_contains($message['name'], $query)) {
                        continue;
                    }
                    $found[] = $this->entry($message);
                    if (count($found) >= $limit) {
                        break;
                    }
                }

                return $found;
            },
            'historyPage' => function (array $peer, int $limit, int $offsetId): array {
                $rowCache = $this->historyStallRows;
                if ($this->nextHistoryStall && $rowCache !== []) {
                    return $rowCache; // frozen cursor — never advances
                }
                $found = [];
                foreach (array_reverse($this->messagesFor($peer)) as $message) {
                    if ($offsetId !== 0 && (int) $message['id'] >= $offsetId) {
                        continue;
                    }
                    $found[] = $this->entry($message);
                    if (count($found) >= $limit) {
                        break;
                    }
                }
                $this->historyStallRows = $found;

                return $found;
            },
            'deleteMessages' => function (array $peer, array $ids): array {
                $this->deletes[] = ['peer' => $peer, 'ids' => array_map('intval', $ids)];
                $ids = array_map('intval', $ids);
                $channelId = (int) ($peer['channel_id'] ?? 0);
                // Revoking a message id removes it from every peer log: a
                // message id is unique per chat, so removing it from the wrong
                // partition would hide a real leak.
                $this->messages = array_values(array_filter(
                    $this->messages,
                    fn (array $message): bool => ! in_array((int) $message['id'], $ids, true),
                ));
                foreach ($this->peerMessages as $peerId => $rows) {
                    $this->peerMessages[$peerId] = array_values(array_filter(
                        $rows,
                        fn (array $message): bool => ! in_array((int) $message['id'], $ids, true),
                    ));
                }
                unset($channelId);

                return ['_' => 'messages.affectedMessages', 'pts' => 0, 'pts_count' => count($ids)];
            },
            'getFile' => function (array $location, int $offset, int $limit): array {
                if ($this->failNextDownloadSeconds !== null) {
                    $seconds = $this->failNextDownloadSeconds;
                    $this->failNextDownloadSeconds = null;
                    throw new FloodWaitException($seconds, "FLOOD_WAIT_{$seconds}", 420);
                }
                $document = $this->documents[(int) ($location['id'] ?? 0)] ?? '';
                $slice = substr($document, $offset, $limit);

                return ['_' => 'upload.file', 'type' => 'document', 'mtime' => 0, 'bytes' => $slice];
            },
        ];
    }

    /** Make the next uploadPart throw (exercises ResilientCall flood path). */
    public function failNextUpload(int $floodSeconds = 2): void
    {
        $this->failNextUploadSeconds = $floodSeconds;
    }

    /** Make the next getFile throw (exercises ResilientCall flood path). */
    public function failNextDownload(int $floodSeconds = 2): void
    {
        $this->failNextDownloadSeconds = $floodSeconds;
    }

    /**
     * Append one message to the flat log AND its peer's partition, and
     * record the send so a test can assert which peer it was addressed to.
     */
    private function record(array $peer, int $id, string $name, ?int $docId, ?string $bytes, ?string $text): void
    {
        $row = ['id' => $id, 'name' => $name, 'doc_id' => $docId, 'bytes' => $bytes, 'text' => $text];
        $channelId = (int) ($peer['channel_id'] ?? 0);
        $this->messages[] = $row;
        $this->peerMessages[$channelId][] = $row;
        $this->sends[] = [
            'kind' => $docId === null ? 'message' : 'document',
            'peer' => $peer,
            'name' => $name,
            'id' => $id,
        ];
    }

    /**
     * Seed a downloadable document message directly on the wire (bypasses
     * the upload part loop — used by the store-service media index tests).
     */
    public function uploadDocument(string $name, string $bytes): int
    {
        $docId = $this->nextDocumentId++;
        $this->documents[$docId] = $bytes;
        $id = $this->nextId++;
        $row = ['id' => $id, 'name' => $name, 'doc_id' => $docId, 'bytes' => $bytes, 'text' => null];
        $this->messages[] = $row;
        $this->peerMessages[$this->seedPeerId][] = $row;

        return $id;
    }

    /** Seed a text message directly on the wire. */
    public function sendText(string $text): int
    {
        $id = $this->nextId++;
        $row = ['id' => $id, 'name' => self::firstLine($text), 'doc_id' => null, 'bytes' => null, 'text' => $text];
        $this->messages[] = $row;
        $this->peerMessages[$this->seedPeerId][] = $row;

        return $id;
    }

    /**
     * Corrupt the stored document bytes served back for a message by name
     * (verify/StoreService tamper detection tests).
     */
    public function tamperDocument(string $name, string $bytes): void
    {
        // Every partition AND the flat log: rows are copied by value into the
        // per-peer partitions, and the fetch closures read the row they were
        // built from, so tampering one copy alone would leave the tampered
        // bytes invisible on the peer a search actually reads.
        $tamper = function (array &$message) use ($name, $bytes): bool {
            if ($message['name'] === $name && $message['doc_id'] !== null) {
                $this->documents[$message['doc_id']] = $bytes;
                $message['bytes'] = $bytes;

                return true;
            }

            return false;
        };

        foreach ($this->messages as $i => $message) {
            $copy = $message;
            if ($tamper($copy)) {
                $this->messages[$i] = $copy;
            }
        }
        foreach ($this->peerMessages as $peerId => $rows) {
            foreach ($rows as $i => $message) {
                $copy = $message;
                if ($tamper($copy)) {
                    $this->peerMessages[$peerId][$i] = $copy;
                }
            }
        }
    }

    public function channelTitleForBucket(string $bucket): string
    {
        return 'teleframe-store:'.$bucket;
    }

    /**
     * A raw wire message row shaped like the real search/history replies
     * (media.document) for the channel's fetchDocument()/downloadDocument()
     * paths.
     *
     * @return array<string, mixed>
     */
    public function wireMessageRow(string $name): ?array
    {
        foreach (array_reverse($this->messages) as $message) {
            if ($message['name'] === $name && $message['doc_id'] !== null) {
                return [
                    'id' => $message['id'],
                    'message' => '',
                    'media' => [
                        '_' => 'messageMediaDocument',
                        'document' => [
                            '_' => 'document',
                            'id' => $message['doc_id'],
                            'access_hash' => 42,
                            'file_reference' => 'ref-'.$message['doc_id'],
                        ],
                    ],
                ];
            }
        }

        return null;
    }

    /** @return array{id: mixed, name: mixed, fetch: \Closure(): string, media?: array<string, mixed>} */
    private function entry(array $message): array
    {
        $entry = [
            'id' => $message['id'],
            'name' => $message['name'],
            'fetch' => fn (): string => is_string($message['bytes']) ? $message['bytes'] : (string) $message['text'],
        ];
        if ($message['doc_id'] !== null) {
            $entry['media'] = [
                '_' => 'messageMediaDocument',
                'document' => [
                    '_' => 'document',
                    'id' => $message['doc_id'],
                    'access_hash' => 42,
                    'file_reference' => 'ref-'.$message['doc_id'],
                    'size' => is_string($message['bytes']) ? strlen($message['bytes']) : 0,
                    'mime_type' => 'application/octet-stream',
                ],
            ];
        }

        return $entry;
    }

    private static function firstLine(string $text): string
    {
        $newline = strpos($text, "\n");

        return $newline === false ? $text : substr($text, 0, $newline);
    }
}
