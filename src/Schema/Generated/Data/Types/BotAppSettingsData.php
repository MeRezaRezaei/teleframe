<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for botAppSettings of BotAppSettings.
 *
 * bytes params carried as base64 strings: placeholder_path
 */
final class BotAppSettingsData extends TlBotAppSettingsAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $placeholderPath,
    public ?int $backgroundColor,
    public ?int $backgroundDarkColor,
    public ?int $headerColor,
    public ?int $headerDarkColor,
    ) {
    }
}
