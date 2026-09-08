<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getMultiWallPapers (crc32 65ad71dc), returns Vector<WallPaper>. */
final class TlAccountGetMultiWallPapersData extends Data
{
    public const METHOD = 'account.getMultiWallPapers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $wallpapers,
    ) {
    }
}
