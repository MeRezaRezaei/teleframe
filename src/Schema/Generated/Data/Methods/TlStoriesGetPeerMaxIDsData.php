<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.getPeerMaxIDs (crc32 78499170), returns Vector<RecentStory>. */
final class TlStoriesGetPeerMaxIDsData extends Data
{
    public const METHOD = 'stories.getPeerMaxIDs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $id,
    ) {
    }
}
