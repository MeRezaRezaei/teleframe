<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reorderPinnedSavedDialogs (crc32 8b716587), returns Bool. */
final class TlMessagesReorderPinnedSavedDialogsData extends Data
{
    public const METHOD = 'messages.reorderPinnedSavedDialogs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $force,
    public array $order,
    ) {
    }
}
