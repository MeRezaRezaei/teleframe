<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.toggleBotInAttachMenu (crc32 69f59d69), returns Bool. */
final class TlMessagesToggleBotInAttachMenuData extends Data
{
    public const METHOD = 'messages.toggleBotInAttachMenu';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $writeAllowed,
    public mixed $bot,
    public mixed $enabled,
    ) {
    }
}
