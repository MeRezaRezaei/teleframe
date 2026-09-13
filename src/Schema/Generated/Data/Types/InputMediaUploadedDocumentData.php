<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaUploadedDocument of InputMedia.
 */
final class InputMediaUploadedDocumentData extends TlInputMediaAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'nosoundVideo' => ['flags', 3],
        'forceFile' => ['flags', 4],
        'spoiler' => ['flags', 5],
    ];

    public function __construct(
    public int $flags,
    public ?bool $nosoundVideo,
    public ?bool $forceFile,
    public ?bool $spoiler,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputFileAbstractData $file,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputFileAbstractData $thumb,
    public string $mimeType,
    public array $attributes,
    public ?array $stickers,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPhotoAbstractData $videoCover,
    public ?int $videoTimestamp,
    public ?int $ttlSeconds,
    ) {
    }
}
