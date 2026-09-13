<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getDialogs (crc32 a0f4cb4f), returns messages.Dialogs. */
final class TlMessagesGetDialogsData extends Data
{
    public const METHOD = 'messages.getDialogs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $excludePinned,
    public ?int $folderId,
    public int $offsetDate,
    public int $offsetId,
    public mixed $offsetPeer,
    public int $limit,
    public int $hash,
    ) {
    }
}
