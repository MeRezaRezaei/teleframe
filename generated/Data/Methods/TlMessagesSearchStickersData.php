<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.searchStickers (crc32 29b1c66a), returns messages.FoundStickers. */
final class TlMessagesSearchStickersData extends Data
{
    public const METHOD = 'messages.searchStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $emojis,
    public string $q,
    public string $emoticon,
    public array $langCode,
    public int $offset,
    public int $limit,
    public int $hash,
    ) {
    }
}
