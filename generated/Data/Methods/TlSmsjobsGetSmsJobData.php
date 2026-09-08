<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method smsjobs.getSmsJob (crc32 778d902f), returns SmsJob. */
final class TlSmsjobsGetSmsJobData extends Data
{
    public const METHOD = 'smsjobs.getSmsJob';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $jobId,
    ) {
    }
}
