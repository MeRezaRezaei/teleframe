<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reorderPinnedDialogs (crc32 3b1adf37), returns Bool. */
final class TlMessagesReorderPinnedDialogsData extends Data
{
    public const METHOD = 'messages.reorderPinnedDialogs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $force,
    public int $folderId,
    public array $order,
    ) {
    }
}
