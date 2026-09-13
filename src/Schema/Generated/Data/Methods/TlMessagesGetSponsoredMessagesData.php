<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSponsoredMessages (crc32 3d6ce850), returns messages.SponsoredMessages. */
final class TlMessagesGetSponsoredMessagesData extends Data
{
    public const METHOD = 'messages.getSponsoredMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public ?int $msgId,
    ) {
    }
}
