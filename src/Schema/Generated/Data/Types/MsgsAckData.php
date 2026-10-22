<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for msgs_ack of MsgsAck.
 */
final class MsgsAckData extends TlMsgsAckAbstractData
{
    public function __construct(
    public array $msgIds,
    ) {
    }
}
