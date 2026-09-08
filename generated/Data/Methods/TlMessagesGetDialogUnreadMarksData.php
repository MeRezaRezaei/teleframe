<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getDialogUnreadMarks (crc32 21202222), returns Vector<DialogPeer>. */
final class TlMessagesGetDialogUnreadMarksData extends Data
{
    public const METHOD = 'messages.getDialogUnreadMarks';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $parentPeer,
    ) {
    }
}
