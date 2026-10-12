<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendQuickReplyMessages (crc32 6c750de1), returns Updates. */
final class TlMessagesSendQuickReplyMessagesData extends Data
{
    public const METHOD = 'messages.sendQuickReplyMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $shortcutId,
    public array $id,
    public array $randomId,
    ) {
    }
}
