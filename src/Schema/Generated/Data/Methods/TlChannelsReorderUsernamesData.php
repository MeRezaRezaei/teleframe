<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.reorderUsernames (crc32 b45ced1d), returns Bool. */
final class TlChannelsReorderUsernamesData extends Data
{
    public const METHOD = 'channels.reorderUsernames';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public array $order,
    ) {
    }
}
