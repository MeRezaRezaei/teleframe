<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.createForumTopic (crc32 2f98c3d5), returns Updates. */
final class TlMessagesCreateForumTopicData extends Data
{
    public const METHOD = 'messages.createForumTopic';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $titleMissing,
    public mixed $peer,
    public string $title,
    public ?int $iconColor,
    public ?int $iconEmojiId,
    public int $randomId,
    public mixed $sendAs,
    ) {
    }
}
