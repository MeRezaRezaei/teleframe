<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.toggleSavedDialogPin (crc32 ac81bbde), returns Bool. */
final class TlMessagesToggleSavedDialogPinData extends Data
{
    public const METHOD = 'messages.toggleSavedDialogPin';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $pinned,
    public mixed $peer,
    ) {
    }
}
