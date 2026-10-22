<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stickers.createStickerSet (crc32 9021ab67), returns messages.StickerSet. */
final class TlStickersCreateStickerSetData extends Data
{
    public const METHOD = 'stickers.createStickerSet';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $masks,
    public ?bool $emojis,
    public ?bool $textColor,
    public mixed $userId,
    public string $title,
    public string $shortName,
    public mixed $thumb,
    public array $stickers,
    public ?string $software,
    ) {
    }
}
