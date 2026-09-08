<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editQuickReplyShortcut (crc32 5c003cef), returns Bool. */
final class TlMessagesEditQuickReplyShortcutData extends Data
{
    public const METHOD = 'messages.editQuickReplyShortcut';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $shortcutId,
    public string $shortcut,
    ) {
    }
}
