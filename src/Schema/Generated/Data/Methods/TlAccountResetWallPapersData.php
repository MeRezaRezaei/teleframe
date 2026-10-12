<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.resetWallPapers (crc32 bb3b9804), returns Bool. */
final class TlAccountResetWallPapersData extends Data
{
    public const METHOD = 'account.resetWallPapers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
