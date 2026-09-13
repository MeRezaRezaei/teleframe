<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getAdminsWithInvites (crc32 3920e6ef), returns messages.ChatAdminsWithInvites. */
final class TlMessagesGetAdminsWithInvitesData extends Data
{
    public const METHOD = 'messages.getAdminsWithInvites';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
