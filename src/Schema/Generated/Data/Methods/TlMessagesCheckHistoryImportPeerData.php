<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.checkHistoryImportPeer (crc32 5dc60f03), returns messages.CheckedHistoryImportPeer. */
final class TlMessagesCheckHistoryImportPeerData extends Data
{
    public const METHOD = 'messages.checkHistoryImportPeer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
