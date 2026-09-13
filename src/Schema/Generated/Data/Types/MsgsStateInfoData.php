<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for msgs_state_info of MsgsStateInfo.
 */
final class MsgsStateInfoData extends TlMsgsStateInfoAbstractData
{
    public function __construct(
    public int $reqMsgId,
    public string $info,
    ) {
    }
}
