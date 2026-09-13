<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for keyboardButtonStyle of KeyboardButtonStyle.
 */
final class KeyboardButtonStyleData extends TlKeyboardButtonStyleAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'bgPrimary' => ['flags', 0],
        'bgDanger' => ['flags', 1],
        'bgSuccess' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $bgPrimary,
    public ?bool $bgDanger,
    public ?bool $bgSuccess,
    public ?int $icon,
    ) {
    }
}
