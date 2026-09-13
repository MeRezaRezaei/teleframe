<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method chatlists.getLeaveChatlistSuggestions (crc32 fdbcd714), returns Vector<Peer>. */
final class TlChatlistsGetLeaveChatlistSuggestionsData extends Data
{
    public const METHOD = 'chatlists.getLeaveChatlistSuggestions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $chatlist,
    ) {
    }
}
