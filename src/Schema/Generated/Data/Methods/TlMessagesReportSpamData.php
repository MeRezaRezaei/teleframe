<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reportSpam (crc32 cf1592db), returns Bool. */
final class TlMessagesReportSpamData extends Data
{
    public const METHOD = 'messages.reportSpam';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
