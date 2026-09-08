<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.inviteConferenceCallParticipant (crc32 bcf22685), returns Updates. */
final class TlPhoneInviteConferenceCallParticipantData extends Data
{
    public const METHOD = 'phone.inviteConferenceCallParticipant';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $video,
    public mixed $call,
    public mixed $userId,
    ) {
    }
}
