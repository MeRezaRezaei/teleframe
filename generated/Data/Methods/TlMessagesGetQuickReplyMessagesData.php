<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getQuickReplyMessages (crc32 94a495c3), returns messages.Messages. */
final class TlMessagesGetQuickReplyMessagesData extends Data
{
    public const METHOD = 'messages.getQuickReplyMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public int $shortcutId,
    public ?array $id,
    public int $hash,
    ) {
    }
}
