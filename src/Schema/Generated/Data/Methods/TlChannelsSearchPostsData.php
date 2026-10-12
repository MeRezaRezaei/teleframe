<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.searchPosts (crc32 f2c4f24d), returns messages.Messages. */
final class TlChannelsSearchPostsData extends Data
{
    public const METHOD = 'channels.searchPosts';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?string $hashtag,
    public ?string $query,
    public int $offsetRate,
    public mixed $offsetPeer,
    public int $offsetId,
    public int $limit,
    public ?int $allowPaidStars,
    ) {
    }
}
