<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.checkSearchPostsFlood (crc32 22567115), returns SearchPostsFlood. */
final class TlChannelsCheckSearchPostsFloodData extends Data
{
    public const METHOD = 'channels.checkSearchPostsFlood';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?string $query,
    ) {
    }
}
