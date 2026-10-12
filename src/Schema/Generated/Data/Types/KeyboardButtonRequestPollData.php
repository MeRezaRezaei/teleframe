<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for keyboardButtonRequestPoll of KeyboardButton.
 */
final class KeyboardButtonRequestPollData extends TlKeyboardButtonAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlKeyboardButtonStyleAbstractData $style,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $quiz,
    public string $text,
    ) {
    }
}
