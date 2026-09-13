<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method smsjobs.finishJob (crc32 4f1ebf24), returns Bool. */
final class TlSmsjobsFinishJobData extends Data
{
    public const METHOD = 'smsjobs.finishJob';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $jobId,
    public ?string $error,
    ) {
    }
}
