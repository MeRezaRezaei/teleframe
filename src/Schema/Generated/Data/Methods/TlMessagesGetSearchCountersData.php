<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSearchCounters (crc32 1bbcf300), returns Vector<messages.SearchCounter>. */
final class TlMessagesGetSearchCountersData extends Data
{
    public const METHOD = 'messages.getSearchCounters';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public mixed $savedPeerId,
    public ?int $topMsgId,
    public array $filters,
    ) {
    }
}
