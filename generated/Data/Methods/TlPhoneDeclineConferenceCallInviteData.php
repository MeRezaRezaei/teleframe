<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.declineConferenceCallInvite (crc32 3c479971), returns Updates. */
final class TlPhoneDeclineConferenceCallInviteData extends Data
{
    public const METHOD = 'phone.declineConferenceCallInvite';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $msgId,
    ) {
    }
}
