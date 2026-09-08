<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method chatlists.hideChatlistUpdates (crc32 66e486fb), returns Bool. */
final class TlChatlistsHideChatlistUpdatesData extends Data
{
    public const METHOD = 'chatlists.hideChatlistUpdates';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $chatlist,
    ) {
    }
}
