<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.toggleUserEmojiStatusPermission (crc32 06de6392), returns Bool. */
final class TlBotsToggleUserEmojiStatusPermissionData extends Data
{
    public const METHOD = 'bots.toggleUserEmojiStatusPermission';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public mixed $enabled,
    ) {
    }
}
