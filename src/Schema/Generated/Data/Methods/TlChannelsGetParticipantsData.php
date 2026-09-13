<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getParticipants (crc32 77ced9d0), returns channels.ChannelParticipants. */
final class TlChannelsGetParticipantsData extends Data
{
    public const METHOD = 'channels.getParticipants';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $filter,
    public int $offset,
    public int $limit,
    public int $hash,
    ) {
    }
}
