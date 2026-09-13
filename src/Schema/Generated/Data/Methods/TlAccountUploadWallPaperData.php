<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.uploadWallPaper (crc32 e39a8f03), returns WallPaper. */
final class TlAccountUploadWallPaperData extends Data
{
    public const METHOD = 'account.uploadWallPaper';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $forChat,
    public mixed $file,
    public string $mimeType,
    public mixed $settings,
    ) {
    }
}
