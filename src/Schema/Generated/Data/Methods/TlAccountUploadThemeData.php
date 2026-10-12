<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.uploadTheme (crc32 1c3db333), returns Document. */
final class TlAccountUploadThemeData extends Data
{
    public const METHOD = 'account.uploadTheme';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $file,
    public mixed $thumb,
    public string $fileName,
    public string $mimeType,
    ) {
    }
}
