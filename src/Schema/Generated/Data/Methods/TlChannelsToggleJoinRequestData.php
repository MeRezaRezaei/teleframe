<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleJoinRequest (crc32 0ecc2618), returns Updates. */
final class TlChannelsToggleJoinRequestData extends Data
{
    public const METHOD = 'channels.toggleJoinRequest';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $applyToInvites,
    public mixed $channel,
    public mixed $enabled,
    public mixed $guardBot,
    ) {
    }
}
