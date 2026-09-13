<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.setMainProfileTab (crc32 5dee78b0), returns Bool. */
final class TlAccountSetMainProfileTabData extends Data
{
    public const METHOD = 'account.setMainProfileTab';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $tab,
    ) {
    }
}
