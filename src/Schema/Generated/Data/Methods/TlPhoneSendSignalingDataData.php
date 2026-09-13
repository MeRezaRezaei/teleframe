<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.sendSignalingData (crc32 ff7a9383), returns Bool. */
final class TlPhoneSendSignalingDataData extends Data
{
    public const METHOD = 'phone.sendSignalingData';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public string $data,
    ) {
    }
}
