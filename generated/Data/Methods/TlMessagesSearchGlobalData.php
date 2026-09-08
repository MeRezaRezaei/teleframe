<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.searchGlobal (crc32 4bc6589a), returns messages.Messages. */
final class TlMessagesSearchGlobalData extends Data
{
    public const METHOD = 'messages.searchGlobal';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $broadcastsOnly,
    public ?bool $groupsOnly,
    public ?bool $usersOnly,
    public ?int $folderId,
    public string $q,
    public mixed $filter,
    public int $minDate,
    public int $maxDate,
    public int $offsetRate,
    public mixed $offsetPeer,
    public int $offsetId,
    public int $limit,
    ) {
    }
}
