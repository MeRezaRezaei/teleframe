<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reorderQuickReplies (crc32 60331907), returns Bool. */
final class TlMessagesReorderQuickRepliesData extends Data
{
    public const METHOD = 'messages.reorderQuickReplies';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $order,
    ) {
    }
}
