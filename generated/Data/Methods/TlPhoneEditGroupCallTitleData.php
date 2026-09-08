<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.editGroupCallTitle (crc32 1ca6ac0a), returns Updates. */
final class TlPhoneEditGroupCallTitleData extends Data
{
    public const METHOD = 'phone.editGroupCallTitle';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public string $title,
    ) {
    }
}
