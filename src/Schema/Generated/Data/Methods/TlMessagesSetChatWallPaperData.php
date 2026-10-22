<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setChatWallPaper (crc32 8ffacae1), returns Updates. */
final class TlMessagesSetChatWallPaperData extends Data
{
    public const METHOD = 'messages.setChatWallPaper';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $forBoth,
    public ?bool $revert,
    public mixed $peer,
    public mixed $wallpaper,
    public mixed $settings,
    public ?int $id,
    ) {
    }
}
