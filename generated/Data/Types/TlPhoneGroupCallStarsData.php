<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phone.groupCallStars of phone.GroupCallStars.
 */
final class TlPhoneGroupCallStarsData extends TlPhoneGroupCallStarsAbstractData
{
    public function __construct(
    public int $totalStars,
    public array $topDonors,
    public array $chats,
    public array $users,
    ) {
    }
}
