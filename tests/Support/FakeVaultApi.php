<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use RuntimeException;

/**
 * Offline fake of the injectable TelegramVault api call map (plan Phase 4,
 * Task 2). It mirrors the live scope contract — findChannel/createChannel,
 * uploadBytes/sendDocument/sendText, findMessagesByName — against in-memory
 * channel/message stores. findMessagesByName deliberately matches LOOSELY
 * (str_contains) so the vault's own string-function prefix/exact detection
 * is what the offline tests exercise — and an EMPTY prefix returns every
 * message (capped at the passed limit), the listing find() rides on.
 * listHistoryPage mirrors messages.getHistory offset_id paging (strictly
 * older rows, newest first) — the raw material of listAllEntries' uncapped
 * walk. deleteMessages drops the given ids from the store
 * (messages.deleteMessages + revoke).
 *
 * PEER KINDS. Each peer is registered with a kind ('channel' = broadcast,
 * 'group' = megagroup supergroup or legacy basic chat) and an optional TL
 * constructor ('channel' | 'chat'), mirroring the live dialog rows the
 * scopeApi() walk classifies. findChannel honours the vault's requested
 * kind and an optional peer id, and createChannel records which kind it was
 * asked for. Both default to the historical channel-only behaviour, so a
 * caller that never passes them sees exactly what it always saw.
 */
final class FakeVaultApi
{
    /** @var list<array{call: string, args: list<mixed>}> every api invocation in order */
    public array $calls = [];

    /** @var array<string, int> peer title → id */
    private array $channels = [];

    /** @var array<string, int> peer title → access hash (0 = never resolved) */
    private array $accessHashes = [];

    /** @var array<int, string> peer id → 'channel' | 'group' (the requested kind) */
    private array $peerKinds = [];

    /** @var array<int, string> peer id → TL constructor: 'channel' | 'chat' */
    private array $peerConstructors = [];

    /** @var array<int, list<array{id: int, name: string, is_text: bool}>> peer id → messages, oldest first */
    private array $messages = [];

    /** @var array<string, string> document name → uploaded bytes */
    private array $documents = [];

    private int $nextChannelId;

    private int $nextMsgId = 1;

    public function __construct(int $nextChannelId = 1000)
    {
        $this->nextChannelId = $nextChannelId;
    }

    /**
     * Pre-seed an EXISTING peer titled $title; returns its id.
     *
     * @param  string  $peerType  'channel' (broadcast) | 'group' (supergroup / basic chat)
     * @param  int|null  $id  pin the id instead of allocating the next one
     */
    public function addChannel(string $title, string $peerType = 'channel', ?int $id = null, string $peerConstructor = 'channel'): int
    {
        $peerId = $id ?? $this->nextChannelId;
        if ($id === null) {
            $this->nextChannelId++;
        }
        $this->channels[$title] = $peerId;
        $this->messages[$peerId] = [];
        $this->accessHashes[$title] = 4242;
        $this->peerKinds[$peerId] = $peerType;
        $this->peerConstructors[$peerId] = $peerConstructor;

        return $peerId;
    }

    /**
     * Make an EXISTING channel report access_hash 0 — the unresolved-hash
     * case the vault must refuse rather than derive a (globally constant)
     * key from.
     */
    public function addChannelWithoutAccessHash(int $channelId): void
    {
        foreach ($this->channels as $title => $id) {
            if ($id === $channelId) {
                $this->accessHashes[$title] = 0;
            }
        }
    }

    /** Post a raw text message (already in the peer, not via the vault). */
    public function postText(array|int $channel, string $text): int
    {
        return $this->appendMessage($channel, $text, true);
    }

    /** Post a raw document message named $name (already in the peer). */
    public function postDocument(array|int $channel, string $name, string $bytes): int
    {
        $this->documents[$name] = $bytes;

        return $this->appendMessage($channel, $name, false);
    }

    /** How many times createChannel was invoked through the map. */
    public function createChannelCalls(): int
    {
        return $this->createChannelCount;
    }

    private int $createChannelCount = 0;

    /** The peer kind of the given id, or null when the peer is unknown. */
    public function peerKind(int $peerId): ?string
    {
        return $this->peerKinds[$peerId] ?? null;
    }

    /** @return array<string, callable> the injectable call map */
    public function map(): array
    {
        return [
            'findChannel' => function (string $title, string $peerType = 'channel', ?int $peerId = null): ?array {
                $this->calls[] = ['call' => 'findChannel', 'args' => [$title, $peerType, $peerId]];

                foreach ($this->channels as $knownTitle => $id) {
                    if ($knownTitle !== $title && $peerId !== $id) {
                        continue;
                    }
                    // Kind must match BOTH ways: a vault asking for a channel
                    // must not be handed a group, and vice versa.
                    if (($this->peerKinds[$id] ?? 'channel') !== $peerType) {
                        continue;
                    }
                    // An explicit id is an identity, not a title: it wins, so a
                    // renamed peer configured by id stays resolvable.
                    if ($peerId !== null && $id !== $peerId) {
                        continue;
                    }

                    return [
                        'id' => $id,
                        'access_hash' => $this->accessHashes[$knownTitle] ?? 0,
                        'peer' => $this->peerConstructors[$id] ?? 'channel',
                    ];
                }

                return null;
            },
            'createChannel' => function (string $title, string $about, string $peerType = 'channel'): array {
                $this->calls[] = ['call' => 'createChannel', 'args' => [$title, $about, $peerType]];
                $this->createChannelCount++;
                $id = $this->addChannel($title, $peerType);

                return [
                    'id' => $id,
                    'access_hash' => $this->accessHashes[$title] ?? 4242,
                    // channels.createChannel always mints a `channel` object,
                    // broadcast or megagroup — never a `chat`.
                    'peer' => 'channel',
                ];
            },
            'uploadBytes' => function (string $name, string $bytes): array {
                $this->calls[] = ['call' => 'uploadBytes', 'args' => [$name, $bytes]];
                $this->documents[$name] = $bytes;

                return [
                    '_' => 'inputFile',
                    'id' => 77,
                    'parts' => 1,
                    'name' => $name,
                    'md5_checksum' => md5($bytes),
                ];
            },
            'sendDocument' => function (array $peer, array $inputFile, string $caption): int {
                $this->calls[] = ['call' => 'sendDocument', 'args' => [$peer, $inputFile, $caption]];
                $name = (string) ($inputFile['name'] ?? '');

                return $this->appendMessage(self::peerKey($peer), $name, false);
            },
            'sendText' => function (array $peer, string $text): int {
                $this->calls[] = ['call' => 'sendText', 'args' => [$peer, $text]];

                return $this->appendMessage(self::peerKey($peer), $text, true);
            },
            'findMessagesByName' => function (array $peer, string $namePrefix, int $limit): array {
                $this->calls[] = ['call' => 'findMessagesByName', 'args' => [$peer, $namePrefix, $limit]];

                $messages = array_reverse($this->messages[self::peerKey($peer)] ?? []); // newest first
                $entries = [];
                foreach ($messages as $message) {
                    if (count($entries) >= $limit) {
                        break;
                    }
                    if (! str_contains($message['name'], $namePrefix)) {
                        continue; // deliberately loose: vault-side detection must filter
                    }
                    $entries[] = $this->entryOf($message);
                }

                return $entries;
            },
            'listHistoryPage' => function (array $peer, int $limit, int $offsetId): array {
                $this->calls[] = ['call' => 'listHistoryPage', 'args' => [$peer, $limit, $offsetId]];

                $entries = [];
                foreach (array_reverse($this->messages[self::peerKey($peer)] ?? []) as $message) { // newest first
                    if ($offsetId !== 0 && $message['id'] >= $offsetId) {
                        continue; // offset_id cursor: strictly-older rows only
                    }
                    $entries[] = $this->entryOf($message);
                    if (count($entries) >= $limit) {
                        break;
                    }
                }

                return $entries;
            },
            'deleteMessages' => function (array $peer, array $ids): array {
                $this->calls[] = ['call' => 'deleteMessages', 'args' => [$peer, $ids]];

                $key = self::peerKey($peer);
                $this->messages[$key] = array_values(array_filter(
                    $this->messages[$key] ?? [],
                    static fn (array $message): bool => ! in_array($message['id'], $ids, true),
                ));

                return ['_' => 'updates.affectedMessages', 'pts' => 1, 'pts_count' => count($ids)];
            },
        ];
    }

    /**
     * The message-store key for an inputPeer. A supergroup dials as
     * inputPeerChannel (channel_id); a legacy basic group dials as
     * inputPeerChat (chat_id). Both name the same peer id.
     *
     * @param  array<string, mixed>  $peer
     */
    private static function peerKey(array $peer): int
    {
        return (int) ($peer['channel_id'] ?? $peer['chat_id'] ?? 0);
    }

    private function appendMessage(array|int $channel, string $name, bool $isText): int
    {
        $id = $this->nextMsgId++;
        $key = is_array($channel) ? self::peerKey($channel) : $channel;
        $this->messages[$key][] = ['id' => $id, 'name' => $name, 'is_text' => $isText];

        return $id;
    }

    /** Api-level entry row (id + name + fetch) shared by the two listing calls. @return array<string, mixed> */
    private function entryOf(array $message): array
    {
        return [
            'id' => $message['id'],
            'name' => $message['name'],
            'fetch' => function (string $name) use ($message): string {
                if ($message['is_text']) {
                    throw new RuntimeException("message {$message['id']} carries no document");
                }
                if (! isset($this->documents[$name])) {
                    throw new RuntimeException("no document named {$name}");
                }

                return $this->documents[$name];
            },
        ];
    }
}
