<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaDocumentExternal of InputMedia.
 */
final class InputMediaDocumentExternalData extends TlInputMediaAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'spoiler' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $spoiler,
    public string $url,
    public ?int $ttlSeconds,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPhotoAbstractData $videoCover,
    public ?int $videoTimestamp,
    ) {
    }
}
