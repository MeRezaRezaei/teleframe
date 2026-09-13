<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputKeyboardButtonUrlAuth of KeyboardButton.
 */
final class InputKeyboardButtonUrlAuthData extends TlKeyboardButtonAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'requestWriteAccess' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $requestWriteAccess,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlKeyboardButtonStyleAbstractData $style,
    public string $text,
    public ?string $fwdText,
    public string $url,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputUserAbstractData $bot,
    ) {
    }
}
