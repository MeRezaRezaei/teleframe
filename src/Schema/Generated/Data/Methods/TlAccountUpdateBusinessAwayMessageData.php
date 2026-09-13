<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateBusinessAwayMessage (crc32 a26a7fa5), returns Bool. */
final class TlAccountUpdateBusinessAwayMessageData extends Data
{
    public const METHOD = 'account.updateBusinessAwayMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $message,
    ) {
    }
}
