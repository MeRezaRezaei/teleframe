<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for wallPaperNoFile of WallPaper.
 */
final class WallPaperNoFileData extends TlWallPaperAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'default' => ['flags', 1],
        'dark' => ['flags', 4],
    ];

    public function __construct(
    public int $id,
    public int $flags,
    public ?bool $default,
    public ?bool $dark,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWallPaperSettingsAbstractData $settings,
    ) {
    }
}
