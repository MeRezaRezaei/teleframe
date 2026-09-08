<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.getStoryViewsList (crc32 7ed23c57), returns stories.StoryViewsList. */
final class TlStoriesGetStoryViewsListData extends Data
{
    public const METHOD = 'stories.getStoryViewsList';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $justContacts,
    public ?bool $reactionsFirst,
    public ?bool $forwardsFirst,
    public mixed $peer,
    public ?string $q,
    public int $id,
    public string $offset,
    public int $limit,
    ) {
    }
}
