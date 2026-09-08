<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputPeerUser of InputPeer.
 */
final class InputPeerUserData extends TlInputPeerAbstractData
{
    public function __construct(
    public int $userId,
    public int $accessHash,
    ) {
    }
}
