<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for requestedPeerChat of RequestedPeer.
 */
final class RequestedPeerChatData extends TlRequestedPeerAbstractData
{
    public function __construct(
    public int $flags,
    public int $chatId,
    public ?string $title,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $photo,
    ) {
    }
}
