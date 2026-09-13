<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for documentAttributeSticker of DocumentAttribute.
 */
final class DocumentAttributeStickerData extends TlDocumentAttributeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'mask' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $mask,
    public string $alt,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputStickerSetAbstractData $stickerset,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMaskCoordsAbstractData $maskCoords,
    ) {
    }
}
