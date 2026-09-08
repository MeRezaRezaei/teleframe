<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for keyboardButtonCallback of KeyboardButton.
 *
 * bytes params carried as base64 strings: data
 */
final class KeyboardButtonCallbackData extends TlKeyboardButtonAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'requiresPassword' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $requiresPassword,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlKeyboardButtonStyleAbstractData $style,
    public string $text,
    public string $data,
    ) {
    }
}
