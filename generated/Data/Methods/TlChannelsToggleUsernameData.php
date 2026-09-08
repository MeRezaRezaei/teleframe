<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleUsername (crc32 50f24105), returns Bool. */
final class TlChannelsToggleUsernameData extends Data
{
    public const METHOD = 'channels.toggleUsername';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public string $username,
    public mixed $active,
    ) {
    }
}
