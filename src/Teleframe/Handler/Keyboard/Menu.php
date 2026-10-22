<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler\Keyboard;

use InvalidArgumentException;

/**
 * Q16 keyboard value object: immutable and hierarchical (rows of buttons),
 * with a deterministic content-hash ``id()`` that is stable across deploys
 * and a ``version()`` byte that rotates the token ``keyId()`` WITHOUT
 * changing the content hash — old tokens go stale in one step.
 *
 * A button is a plain array ``{text, route, arg?}``: ``route`` is the uprate
 * route string registered in the shared handler table, ``arg`` is the sscanf
 * token signed into the callback_data. ``render()`` emits the exact Bot API
 * ``InlineKeyboardMarkup`` array the send engine attaches under
 * ``reply_markup``, with every button signed for its target chat/message.
 */
final class Menu
{
    /**
     * @param list<list<array{text: string, route: string, arg?: string}>> $rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly int $version = 1,
    ) {
        self::assertRows($this->rows);
        if ($this->version < 1 || $this->version > 255) {
            throw new InvalidArgumentException('Menu version must be a byte (1..255).');
        }
    }

    /**
     * @return array{text: string, route: string, arg?: string}
     */
    public static function button(string $text, string $route, ?string $arg = null): array
    {
        $button = ['text' => $text, 'route' => $route];
        if ($arg !== null && $arg !== '') {
            $button['arg'] = $arg;
        }

        return $button;
    }

    /**
     * Flat button lookup, 0-based and row-major — the ``keyIdx`` position the
     * registry resolves. Null when the index is out of range.
     *
     * @return array{text: string, route: string, arg?: string}|null
     */
    public function buttonAt(int $keyIdx): ?array
    {
        $i = 0;
        foreach ($this->rows as $row) {
            foreach ($row as $button) {
                if ($i === $keyIdx) {
                    return $button;
                }
                ++$i;
            }
        }

        return null;
    }

    /** Total buttons across all rows (row-major). */
    public function count(): int
    {
        $count = 0;
        foreach ($this->rows as $row) {
            $count += count($row);
        }

        return $count;
    }

    /**
     * Deterministic content-hash identity (Q16): sha1 over the canonical
     * button shapes. Stable across deploys and processes, independent of
     * version().
     */
    public function id(): string
    {
        return sha1($this->canonical());
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * The token key id = 8 content-hash nibbles + the version byte. Rotation
     * (Q16) bumps the version so keyId() changes while id() stays stable —
     * old tokens stop resolving in the live registry ("menu expired").
     */
    public function keyId(): string
    {
        return substr($this->id(), 0, 8) . dechex($this->version);
    }

    /**
     * Bot API InlineKeyboardMarkup with every callback_data signed for the
     * target chat (and message id when known). Pass ``$bindMsgId = null`` for
     * a pre-send render: the token then stays chat-bound until the message
     * exists (the MenuRouter confirms both scopes on decode).
     *
     * @return array{inline_keyboard: list<list<array{text: string, callback_data: string}>>}
     */
    public function render(
        string $secret,
        int|string $bindChatId,
        int|string|null $bindMsgId = null,
    ): array {
        $rows = [];
        $keyIdx = 0;
        foreach ($this->rows as $row) {
            $buttons = [];
            foreach ($row as $button) {
                $buttons[] = [
                    'text' => $button['text'],
                    'callback_data' => CallbackData::encode(
                        $this->keyId(),
                        $keyIdx,
                        $button['arg'] ?? '',
                        $bindMsgId,
                        $bindChatId,
                        $secret,
                    ),
                ];
                ++$keyIdx;
            }
            $rows[] = $buttons;
        }

        return ['inline_keyboard' => $rows];
    }

    private function canonical(): string
    {
        $flat = [];
        foreach ($this->rows as $row) {
            foreach ($row as $button) {
                $flat[] = [$button['text'], $button['route'], $button['arg'] ?? ''];
            }
        }

        return (string) json_encode($flat, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @param array<mixed> $rows */
    private static function assertRows(array $rows): void
    {
        foreach ($rows as $row) {
            if (! is_array($row) || $row === []) {
                throw new InvalidArgumentException('Every menu row must be a non-empty list of buttons.');
            }
            foreach ($row as $button) {
                if (! isset($button['text']) || ! is_string($button['text']) || $button['text'] === '') {
                    throw new InvalidArgumentException('Every button needs a non-empty string text.');
                }
                if (! isset($button['route']) || ! is_string($button['route']) || $button['route'] === '') {
                    throw new InvalidArgumentException('Every button needs a non-empty string route.');
                }
                if (isset($button['arg']) && ! is_string($button['arg'])) {
                    throw new InvalidArgumentException('Button arg must be a string when present.');
                }
            }
        }
    }
}