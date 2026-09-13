<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stickerSet of StickerSet.
 */
final class StickerSetData extends TlStickerSetAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'archived' => ['flags', 1],
        'official' => ['flags', 2],
        'masks' => ['flags', 3],
        'emojis' => ['flags', 7],
        'textColor' => ['flags', 9],
        'channelEmojiStatus' => ['flags', 10],
        'creator' => ['flags', 11],
    ];

    public function __construct(
    public int $flags,
    public ?bool $archived,
    public ?bool $official,
    public ?bool $masks,
    public ?bool $emojis,
    public ?bool $textColor,
    public ?bool $channelEmojiStatus,
    public ?bool $creator,
    public ?int $installedDate,
    public int $id,
    public int $accessHash,
    public string $title,
    public string $shortName,
    public ?array $thumbs,
    public ?int $thumbDcId,
    public ?int $thumbVersion,
    public ?int $thumbDocumentId,
    public int $count,
    public int $hash,
    ) {
    }
}
