<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updatePeerWallpaper of Update.
 */
final class UpdatePeerWallpaperData extends TlUpdateAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'wallpaperOverridden' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $wallpaperOverridden,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWallPaperAbstractData $wallpaper,
    ) {
    }
}
