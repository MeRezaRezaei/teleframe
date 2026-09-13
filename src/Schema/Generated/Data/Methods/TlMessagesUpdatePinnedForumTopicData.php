<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.updatePinnedForumTopic (crc32 175df251), returns Updates. */
final class TlMessagesUpdatePinnedForumTopicData extends Data
{
    public const METHOD = 'messages.updatePinnedForumTopic';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $topicId,
    public mixed $pinned,
    ) {
    }
}
