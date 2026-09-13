<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleViewForumAsMessages (crc32 9738bb15), returns Updates. */
final class TlChannelsToggleViewForumAsMessagesData extends Data
{
    public const METHOD = 'channels.toggleViewForumAsMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $enabled,
    ) {
    }
}
