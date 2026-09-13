<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.uploadMedia (crc32 14967978), returns MessageMedia. */
final class TlMessagesUploadMediaData extends Data
{
    public const METHOD = 'messages.uploadMedia';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?string $businessConnectionId,
    public mixed $peer,
    public mixed $media,
    ) {
    }
}
