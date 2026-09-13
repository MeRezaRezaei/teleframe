<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.checkUsername (crc32 10e6bd2c), returns Bool. */
final class TlChannelsCheckUsernameData extends Data
{
    public const METHOD = 'channels.checkUsername';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public string $username,
    ) {
    }
}
