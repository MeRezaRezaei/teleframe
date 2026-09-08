<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getAdminedPublicChannels (crc32 f8b036af), returns messages.Chats. */
final class TlChannelsGetAdminedPublicChannelsData extends Data
{
    public const METHOD = 'channels.getAdminedPublicChannels';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $byLocation,
    public ?bool $checkLimit,
    public ?bool $forPersonal,
    ) {
    }
}
