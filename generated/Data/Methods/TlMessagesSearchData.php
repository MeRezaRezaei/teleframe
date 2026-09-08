<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.search (crc32 29ee847a), returns messages.Messages. */
final class TlMessagesSearchData extends Data
{
    public const METHOD = 'messages.search';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public string $q,
    public mixed $fromId,
    public mixed $savedPeerId,
    public ?array $savedReaction,
    public ?int $topMsgId,
    public mixed $filter,
    public int $minDate,
    public int $maxDate,
    public int $offsetId,
    public int $addOffset,
    public int $limit,
    public int $maxId,
    public int $minId,
    public int $hash,
    ) {
    }
}
