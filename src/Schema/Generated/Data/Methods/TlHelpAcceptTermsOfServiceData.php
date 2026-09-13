<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.acceptTermsOfService (crc32 ee72f79a), returns Bool. */
final class TlHelpAcceptTermsOfServiceData extends Data
{
    public const METHOD = 'help.acceptTermsOfService';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    ) {
    }
}
