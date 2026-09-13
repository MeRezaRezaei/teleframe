<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reportEncryptedSpam (crc32 4b0c8c0f), returns Bool. */
final class TlMessagesReportEncryptedSpamData extends Data
{
    public const METHOD = 'messages.reportEncryptedSpam';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
