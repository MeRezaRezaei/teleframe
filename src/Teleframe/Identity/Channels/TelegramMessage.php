<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity\Channels;

/**
 * Telegram notification payload: a tiny value object carrying the send
 * fields ({fields:array, ...} — the shape the channel hands to the engine).
 * Fields follow the Bot API sendMessage vocabulary: `text` plus any options
 * (parse_mode, entities, reply_markup, ...).
 */
final class TelegramMessage
{
    /**
     * @param array<string, mixed> $fields
     */
    public function __construct(
        public readonly array $fields = [],
    ) {
    }

    /**
     * Build a text message. `$text` wins over any `text` option key.
     *
     * @param array<string, mixed> $options
     */
    public static function text(string $text, array $options = []): self
    {
        return new self(['text' => $text] + $options);
    }

    /**
     * The message body (the `text` field). Named `body` so the static
     * `text()` factory and the getter coexist (PHP forbids a static and an
     * instance method sharing the name `text`).
     */
    public function body(): string
    {
        return (string) ($this->fields['text'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->fields;
    }
}