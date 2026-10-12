<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageEmpty of Message.
 */
final class MessageEmptyData extends TlMessageAbstractData
{
    public function __construct(
    public int $flags,
    public int $id,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peerId,
    ) {
    }
}
