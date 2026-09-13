<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.saveDefaultGroupCallJoinAs (crc32 575e1f8c), returns Bool. */
final class TlPhoneSaveDefaultGroupCallJoinAsData extends Data
{
    public const METHOD = 'phone.saveDefaultGroupCallJoinAs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $joinAs,
    ) {
    }
}
