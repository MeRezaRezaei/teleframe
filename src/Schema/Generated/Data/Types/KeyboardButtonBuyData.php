<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for keyboardButtonBuy of KeyboardButton.
 */
final class KeyboardButtonBuyData extends TlKeyboardButtonAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlKeyboardButtonStyleAbstractData $style,
    public string $text,
    ) {
    }
}
