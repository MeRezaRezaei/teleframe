<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaUploadedPhoto of InputMedia.
 */
final class InputMediaUploadedPhotoData extends TlInputMediaAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'spoiler' => ['flags', 2],
        'livePhoto' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $spoiler,
    public ?bool $livePhoto,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputFileAbstractData $file,
    public ?array $stickers,
    public ?int $ttlSeconds,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputDocumentAbstractData $video,
    ) {
    }
}
