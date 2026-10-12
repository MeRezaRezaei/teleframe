<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.getStoryReactionsList (crc32 b9b2881f), returns stories.StoryReactionsList. */
final class TlStoriesGetStoryReactionsListData extends Data
{
    public const METHOD = 'stories.getStoryReactionsList';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $forwardsFirst,
    public mixed $peer,
    public int $id,
    public mixed $reaction,
    public ?string $offset,
    public int $limit,
    ) {
    }
}
