<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.toggleGroupCallSettings (crc32 974392f2), returns Updates. */
final class TlPhoneToggleGroupCallSettingsData extends Data
{
    public const METHOD = 'phone.toggleGroupCallSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $resetInviteHash,
    public mixed $call,
    public mixed $joinMuted,
    public mixed $messagesEnabled,
    public ?int $sendPaidMessagesStars,
    ) {
    }
}
