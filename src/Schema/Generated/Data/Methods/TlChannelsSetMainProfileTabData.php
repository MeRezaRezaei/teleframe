<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.setMainProfileTab (crc32 3583fcb1), returns Bool. */
final class TlChannelsSetMainProfileTabData extends Data
{
    public const METHOD = 'channels.setMainProfileTab';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $tab,
    ) {
    }
}
