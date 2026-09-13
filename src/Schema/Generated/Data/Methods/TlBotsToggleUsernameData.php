<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.toggleUsername (crc32 053ca973), returns Bool. */
final class TlBotsToggleUsernameData extends Data
{
    public const METHOD = 'bots.toggleUsername';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public string $username,
    public mixed $active,
    ) {
    }
}
