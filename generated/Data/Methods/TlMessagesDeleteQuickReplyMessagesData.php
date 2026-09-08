<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteQuickReplyMessages (crc32 e105e910), returns Updates. */
final class TlMessagesDeleteQuickReplyMessagesData extends Data
{
    public const METHOD = 'messages.deleteQuickReplyMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $shortcutId,
    public array $id,
    ) {
    }
}
