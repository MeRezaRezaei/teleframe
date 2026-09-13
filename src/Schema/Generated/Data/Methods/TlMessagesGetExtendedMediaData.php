<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getExtendedMedia (crc32 84f80814), returns Updates. */
final class TlMessagesGetExtendedMediaData extends Data
{
    public const METHOD = 'messages.getExtendedMedia';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public array $id,
    ) {
    }
}
