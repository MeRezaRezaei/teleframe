<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendScreenshotNotification (crc32 a1405817), returns Updates. */
final class TlMessagesSendScreenshotNotificationData extends Data
{
    public const METHOD = 'messages.sendScreenshotNotification';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $replyTo,
    public int $randomId,
    ) {
    }
}
