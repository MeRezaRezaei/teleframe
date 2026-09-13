<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.saveDefaultSendAs (crc32 4167add1), returns Bool. */
final class TlPhoneSaveDefaultSendAsData extends Data
{
    public const METHOD = 'phone.saveDefaultSendAs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public mixed $sendAs,
    ) {
    }
}
