<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for wallPaperSettings of WallPaperSettings.
 */
final class WallPaperSettingsData extends TlWallPaperSettingsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'blur' => ['flags', 1],
        'motion' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $blur,
    public ?bool $motion,
    public ?int $backgroundColor,
    public ?int $secondBackgroundColor,
    public ?int $thirdBackgroundColor,
    public ?int $fourthBackgroundColor,
    public ?int $intensity,
    public ?int $rotation,
    public ?string $emoticon,
    ) {
    }
}
