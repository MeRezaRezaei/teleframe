<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.checkQuickReplyShortcut (crc32 f1d0fbd3), returns Bool. */
final class TlMessagesCheckQuickReplyShortcutData extends Data
{
    public const METHOD = 'messages.checkQuickReplyShortcut';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $shortcut,
    ) {
    }
}
