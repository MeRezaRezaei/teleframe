<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reportReadMetrics (crc32 4067c5e6), returns Bool. */
final class TlMessagesReportReadMetricsData extends Data
{
    public const METHOD = 'messages.reportReadMetrics';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public array $metrics,
    ) {
    }
}
