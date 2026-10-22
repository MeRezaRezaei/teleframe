<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for availableEffect of AvailableEffect.
 */
final class AvailableEffectData extends TlAvailableEffectAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'premiumRequired' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $premiumRequired,
    public int $id,
    public string $emoticon,
    public ?int $staticIconId,
    public int $effectStickerId,
    public ?int $effectAnimationId,
    ) {
    }
}
