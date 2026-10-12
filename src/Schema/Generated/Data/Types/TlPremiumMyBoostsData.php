<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for premium.myBoosts of premium.MyBoosts.
 */
final class TlPremiumMyBoostsData extends TlPremiumMyBoostsAbstractData
{
    public function __construct(
    public array $myBoosts,
    public array $chats,
    public array $users,
    ) {
    }
}
