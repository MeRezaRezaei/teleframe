<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleForum (crc32 3ff75734), returns Updates. */
final class TlChannelsToggleForumData extends Data
{
    public const METHOD = 'channels.toggleForum';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $enabled,
    public mixed $tabs,
    ) {
    }
}
