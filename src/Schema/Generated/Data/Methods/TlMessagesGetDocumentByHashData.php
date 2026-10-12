<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getDocumentByHash (crc32 b1f2061f), returns Document. */
final class TlMessagesGetDocumentByHashData extends Data
{
    public const METHOD = 'messages.getDocumentByHash';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $sha256,
    public int $size,
    public string $mimeType,
    ) {
    }
}
