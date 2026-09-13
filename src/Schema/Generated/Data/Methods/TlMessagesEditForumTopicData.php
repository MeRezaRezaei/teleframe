<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editForumTopic (crc32 cecc1134), returns Updates. */
final class TlMessagesEditForumTopicData extends Data
{
    public const METHOD = 'messages.editForumTopic';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public int $topicId,
    public ?string $title,
    public ?int $iconEmojiId,
    public mixed $closed,
    public mixed $hidden,
    ) {
    }
}
