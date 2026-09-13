<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.report (crc32 19d8eb45), returns ReportResult. */
final class TlStoriesReportData extends Data
{
    public const METHOD = 'stories.report';

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
