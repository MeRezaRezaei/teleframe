<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.unpinAllMessages (crc32 062dd747), returns messages.AffectedHistory. */
final class TlMessagesUnpinAllMessagesData extends Data
{
    public const METHOD = 'messages.unpinAllMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public ?int $topMsgId,
    public mixed $savedPeerId,
    ) {
    }
}
