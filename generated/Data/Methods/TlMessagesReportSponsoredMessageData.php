<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reportSponsoredMessage (crc32 12cbf0c4), returns channels.SponsoredMessageReportResult. */
final class TlMessagesReportSponsoredMessageData extends Data
{
    public const METHOD = 'messages.reportSponsoredMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $randomId,
    public string $option,
    ) {
    }
}
