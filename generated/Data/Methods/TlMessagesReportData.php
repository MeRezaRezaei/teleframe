<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.report (crc32 fc78af9b), returns ReportResult. */
final class TlMessagesReportData extends Data
{
    public const METHOD = 'messages.report';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public array $id,
    public string $option,
    public string $message,
    ) {
    }
}
