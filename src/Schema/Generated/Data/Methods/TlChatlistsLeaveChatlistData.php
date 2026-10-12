<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method chatlists.leaveChatlist (crc32 74fae13a), returns Updates. */
final class TlChatlistsLeaveChatlistData extends Data
{
    public const METHOD = 'chatlists.leaveChatlist';

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
