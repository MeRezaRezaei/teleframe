<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.exportStoryLink (crc32 7b8def20), returns ExportedStoryLink. */
final class TlStoriesExportStoryLinkData extends Data
{
    public const METHOD = 'stories.exportStoryLink';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $id,
    ) {
    }
}
