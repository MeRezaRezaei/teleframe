<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pageBlockEmbed of PageBlock.
 */
final class PageBlockEmbedData extends TlPageBlockAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'fullWidth' => ['flags', 0],
        'allowScrolling' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $fullWidth,
    public ?bool $allowScrolling,
    public ?string $url,
    public ?string $html,
    public ?int $posterPhotoId,
    public ?int $w,
    public ?int $h,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPageCaptionAbstractData $caption,
    ) {
    }
}
