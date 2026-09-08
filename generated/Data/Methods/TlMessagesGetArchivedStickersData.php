<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getArchivedStickers (crc32 57f17692), returns messages.ArchivedStickers. */
final class TlMessagesGetArchivedStickersData extends Data
{
    public const METHOD = 'messages.getArchivedStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $masks,
    public ?bool $emojis,
    public int $offsetId,
    public int $limit,
    ) {
    }
}
