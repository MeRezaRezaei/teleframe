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
 */
final class FakeVaultApi
{
    /** @var list<array{call: string, args: list<mixed>}> every api invocation in order */
    public array $calls = [];

    /** @var array<string, int> channel title → id */
    private array $channels = [];

    /** @var array<int, list<array{id: int, name: string, is_text: bool}>> channel id → messages, oldest first */
    private array $messages = [];

    /** @var array<string, string> document name → uploaded bytes */
    private array $documents = [];

    private int $nextChannelId;

    private int $nextMsgId = 1;

    public function __construct(int $nextChannelId = 1000)
    {
        $this->nextChannelId = $nextChannelId;
    }

    /** Pre-seed an existing channel titled $title; returns its id. */
    public function addChannel(string $title): int
    {
        $id = $this->nextChannelId++;
        $this->channels[$title] = $id;
        $this->messages[$id] = [];

        return $id;
    }

    /** Post a raw text message (already in the channel, not via the vault). */
    public function postText(array|int $channel, string $text): int
    {
        return $this->appendMessage($channel, $text, true);
    }

    /** Post a raw document message named $name (already in the channel). */
    public function postDocument(array|int $channel, string $name, string $bytes): int
    {
        $this->documents[$name] = $bytes;

        return $this->appendMessage($channel, $name, false);
    }

    /** How many times createChannel was invoked through the map. */
    public function createChannelCalls(): int
    {
        $n = 0;
        foreach ($this->calls as $call) {
            if ($call['call'] === 'createChannel') {
                $n++;
            }
        }

        return $n;
    }

    /** @return array<string, callable> the injectable call map */
    public function map(): array
    {
        return [
            'findChannel' => function (string $title): ?array {
                $this->calls[] = ['call' => 'findChannel', 'args' => [$title]];

                return isset($this->channels[$title])
                    ? ['id' => $this->channels[$title], 'access_hash' => 4242]
                    : null;
            },
            'createChannel' => function (string $title, string $about): array {
                $this->calls[] = ['call' => 'createChannel', 'args' => [$title, $about]];
                $id = $this->addChannel($title);

                return ['id' => $id, 'access_hash' => 4242];
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

                return $this->appendMessage($peer['channel_id'] ?? 0, $name, false);
            },
            'sendText' => function (array $peer, string $text): int {
                $this->calls[] = ['call' => 'sendText', 'args' => [$peer, $text]];

                return $this->appendMessage($peer['channel_id'] ?? 0, $text, true);
            },
            'findMessagesByName' => function (array $peer, string $namePrefix, int $limit): array {
                $this->calls[] = ['call' => 'findMessagesByName', 'args' => [$peer, $namePrefix, $limit]];

                $messages = array_reverse($this->messages[$peer['channel_id'] ?? 0] ?? []); // newest first
                $entries = [];
                foreach ($messages as $message) {
                    if (count($entries) >= $limit) {
                        break;
                    }
                    if (!str_contains($message['name'], $namePrefix)) {
                        continue; // deliberately loose: vault-side detection must filter
                    }
                    $entries[] = $this->entryOf($message);
                }

                return $entries;
            },
            'listHistoryPage' => function (array $peer, int $limit, int $offsetId): array {
                $this->calls[] = ['call' => 'listHistoryPage', 'args' => [$peer, $limit, $offsetId]];

                $entries = [];
                foreach (array_reverse($this->messages[$peer['channel_id'] ?? 0] ?? []) as $message) { // newest first
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

                $key = $peer['channel_id'] ?? 0;
                $this->messages[$key] = array_values(array_filter(
                    $this->messages[$key] ?? [],
                    static fn (array $message): bool => !in_array($message['id'], $ids, true),
                ));

                return ['_' => 'updates.affectedMessages', 'pts' => 1, 'pts_count' => count($ids)];
            },
        ];
    }

    private function appendMessage(array|int $channel, string $name, bool $isText): int
    {
        $id = $this->nextMsgId++;
        $key = is_array($channel) ? ($channel['channel_id'] ?? 0) : $channel;
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
                if (!isset($this->documents[$name])) {
                    throw new RuntimeException("no document named {$name}");
                }

                return $this->documents[$name];
            },
        ];
    }
}
