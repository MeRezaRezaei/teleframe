<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.saveDefaultSendAs (crc32 ccfddf96), returns Bool. */
final class TlMessagesSaveDefaultSendAsData extends Data
{
    public const METHOD = 'messages.saveDefaultSendAs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $sendAs,
    ) {
    }
}
