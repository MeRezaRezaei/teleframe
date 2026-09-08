<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.joinGroupCallPresentation (crc32 cbea6bc4), returns Updates. */
final class TlPhoneJoinGroupCallPresentationData extends Data
{
    public const METHOD = 'phone.joinGroupCallPresentation';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public mixed $params,
    ) {
    }
}
