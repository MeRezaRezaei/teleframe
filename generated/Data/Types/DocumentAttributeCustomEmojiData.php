<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for documentAttributeCustomEmoji of DocumentAttribute.
 */
final class DocumentAttributeCustomEmojiData extends TlDocumentAttributeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'free' => ['flags', 0],
        'textColor' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $free,
    public ?bool $textColor,
    public string $alt,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputStickerSetAbstractData $stickerset,
    ) {
    }
}
