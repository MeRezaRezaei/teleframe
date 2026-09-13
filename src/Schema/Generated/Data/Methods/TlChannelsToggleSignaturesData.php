<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleSignatures (crc32 418d549c), returns Updates. */
final class TlChannelsToggleSignaturesData extends Data
{
    public const METHOD = 'channels.toggleSignatures';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $signaturesEnabled,
    public ?bool $profilesEnabled,
    public mixed $channel,
    ) {
    }
}
