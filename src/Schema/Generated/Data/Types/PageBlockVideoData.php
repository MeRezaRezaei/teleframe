<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pageBlockVideo of PageBlock.
 */
final class PageBlockVideoData extends TlPageBlockAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'autoplay' => ['flags', 0],
        'loop' => ['flags', 1],
        'spoiler' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $autoplay,
    public ?bool $loop,
    public ?bool $spoiler,
    public int $videoId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPageCaptionAbstractData $caption,
    ) {
    }
}
