<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for themeSettings of ThemeSettings.
 */
final class ThemeSettingsData extends TlThemeSettingsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'messageColorsAnimated' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $messageColorsAnimated,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBaseThemeAbstractData $baseTheme,
    public int $accentColor,
    public ?int $outboxAccentColor,
    public ?array $messageColors,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWallPaperAbstractData $wallpaper,
    ) {
    }
}
