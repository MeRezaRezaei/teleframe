<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method chatlists.joinChatlistUpdates (crc32 e089f8f5), returns Updates. */
final class TlChatlistsJoinChatlistUpdatesData extends Data
{
    public const METHOD = 'chatlists.joinChatlistUpdates';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $chatlist,
    public array $peers,
    ) {
    }
}
