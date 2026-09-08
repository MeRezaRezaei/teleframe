<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for documentAttributeVideo of DocumentAttribute.
 */
final class DocumentAttributeVideoData extends TlDocumentAttributeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'roundMessage' => ['flags', 0],
        'supportsStreaming' => ['flags', 1],
        'nosound' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $roundMessage,
    public ?bool $supportsStreaming,
    public ?bool $nosound,
    public float $duration,
    public int $w,
    public int $h,
    public ?int $preloadPrefixSize,
    public ?float $videoStartTs,
    public ?string $videoCodec,
    ) {
    }
}
