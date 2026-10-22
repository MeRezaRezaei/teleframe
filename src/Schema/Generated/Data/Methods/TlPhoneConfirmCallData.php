<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.confirmCall (crc32 2efe1722), returns phone.PhoneCall. */
final class TlPhoneConfirmCallData extends Data
{
    public const METHOD = 'phone.confirmCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public string $gA,
    public int $keyFingerprint,
    public mixed $protocol,
    ) {
    }
}
