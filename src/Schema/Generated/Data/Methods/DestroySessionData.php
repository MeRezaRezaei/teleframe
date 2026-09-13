<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method destroy_session (crc32 e7512126), returns DestroySessionRes. */
final class DestroySessionData extends Data
{
    public const METHOD = 'destroy_session';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $sessionId,
    ) {
    }
}
