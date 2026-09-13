<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.searchPosts (crc32 d1810907), returns stories.FoundStories. */
final class TlStoriesSearchPostsData extends Data
{
    public const METHOD = 'stories.searchPosts';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?string $hashtag,
    public mixed $area,
    public mixed $peer,
    public string $offset,
    public int $limit,
    ) {
    }
}
