<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.getGroupCall (crc32 041845db), returns phone.GroupCall. */
final class TlPhoneGetGroupCallData extends Data
{
    public const METHOD = 'phone.getGroupCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public int $limit,
    ) {
    }
}
