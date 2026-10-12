<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phone.groupCall of phone.GroupCall.
 */
final class TlPhoneGroupCallData extends TlPhoneGroupCallAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGroupCallAbstractData $call,
    public array $participants,
    public string $participantsNextOffset,
    public array $chats,
    public array $users,
    ) {
    }
}
