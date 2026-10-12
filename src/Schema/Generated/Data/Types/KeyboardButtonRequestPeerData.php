<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for keyboardButtonRequestPeer of KeyboardButton.
 */
final class KeyboardButtonRequestPeerData extends TlKeyboardButtonAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlKeyboardButtonStyleAbstractData $style,
    public string $text,
    public int $buttonId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRequestPeerTypeAbstractData $peerType,
    public int $maxQuantity,
    ) {
    }
}
