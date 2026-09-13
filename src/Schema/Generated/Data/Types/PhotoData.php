<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for photo of Photo.
 *
 * bytes params carried as base64 strings: file_reference
 */
final class PhotoData extends TlPhotoAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hasStickers' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hasStickers,
    public int $id,
    public int $accessHash,
    public string $fileReference,
    public int $date,
    public array $sizes,
    public ?array $videoSizes,
    public int $dcId,
    ) {
    }
}
