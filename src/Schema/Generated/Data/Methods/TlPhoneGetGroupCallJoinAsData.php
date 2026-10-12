<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.getGroupCallJoinAs (crc32 ef7c213a), returns phone.JoinAsPeers. */
final class TlPhoneGetGroupCallJoinAsData extends Data
{
    public const METHOD = 'phone.getGroupCallJoinAs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
