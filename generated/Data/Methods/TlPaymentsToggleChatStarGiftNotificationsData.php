<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.toggleChatStarGiftNotifications (crc32 60eaefa1), returns Bool. */
final class TlPaymentsToggleChatStarGiftNotificationsData extends Data
{
    public const METHOD = 'payments.toggleChatStarGiftNotifications';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $enabled,
    public mixed $peer,
    ) {
    }
}
