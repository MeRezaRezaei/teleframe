<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.editStory (crc32 2c63a72b), returns Updates. */
final class TlStoriesEditStoryData extends Data
{
    public const METHOD = 'stories.editStory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public int $id,
    public mixed $media,
    public ?array $mediaAreas,
    public ?string $caption,
    public ?array $entities,
    public ?array $privacyRules,
    public mixed $music,
    ) {
    }
}
