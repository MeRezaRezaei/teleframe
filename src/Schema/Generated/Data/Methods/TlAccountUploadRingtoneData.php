<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.uploadRingtone (crc32 831a83a2), returns Document. */
final class TlAccountUploadRingtoneData extends Data
{
    public const METHOD = 'account.uploadRingtone';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $file,
    public string $fileName,
    public string $mimeType,
    ) {
    }
}
