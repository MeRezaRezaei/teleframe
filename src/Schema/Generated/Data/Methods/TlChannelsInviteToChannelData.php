<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.inviteToChannel (crc32 c9e33d54), returns messages.InvitedUsers. */
final class TlChannelsInviteToChannelData extends Data
{
    public const METHOD = 'channels.inviteToChannel';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public array $users,
    ) {
    }
}
