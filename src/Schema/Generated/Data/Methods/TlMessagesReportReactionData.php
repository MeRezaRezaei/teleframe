<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reportReaction (crc32 3f64c076), returns Bool. */
final class TlMessagesReportReactionData extends Data
{
    public const METHOD = 'messages.reportReaction';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $id,
    public mixed $reactionPeer,
    ) {
    }
}
