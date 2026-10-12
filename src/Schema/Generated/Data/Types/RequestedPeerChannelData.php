<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for requestedPeerChannel of RequestedPeer.
 */
final class RequestedPeerChannelData extends TlRequestedPeerAbstractData
{
    public function __construct(
    public int $flags,
    public int $channelId,
    public ?string $title,
    public ?string $username,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $photo,
    ) {
    }
}
