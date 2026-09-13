<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateStatus (crc32 6628562c), returns Bool. */
final class TlAccountUpdateStatusData extends Data
{
    public const METHOD = 'account.updateStatus';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $offline,
    ) {
    }
}
