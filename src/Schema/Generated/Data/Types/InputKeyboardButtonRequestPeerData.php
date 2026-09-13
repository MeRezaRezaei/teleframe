<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputKeyboardButtonRequestPeer of KeyboardButton.
 */
final class InputKeyboardButtonRequestPeerData extends TlKeyboardButtonAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'nameRequested' => ['flags', 0],
        'usernameRequested' => ['flags', 1],
        'photoRequested' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $nameRequested,
    public ?bool $usernameRequested,
    public ?bool $photoRequested,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlKeyboardButtonStyleAbstractData $style,
    public string $text,
    public int $buttonId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRequestPeerTypeAbstractData $peerType,
    public int $maxQuantity,
    ) {
    }
}
