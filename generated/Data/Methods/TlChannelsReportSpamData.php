<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.reportSpam (crc32 f44a8315), returns Bool. */
final class TlChannelsReportSpamData extends Data
{
    public const METHOD = 'channels.reportSpam';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $participant,
    public array $id,
    ) {
    }
}
