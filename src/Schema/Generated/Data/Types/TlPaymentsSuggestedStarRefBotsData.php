<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.suggestedStarRefBots of payments.SuggestedStarRefBots.
 */
final class TlPaymentsSuggestedStarRefBotsData extends TlPaymentsSuggestedStarRefBotsAbstractData
{
    public function __construct(
    public int $flags,
    public int $count,
    public array $suggestedBots,
    public array $users,
    public ?string $nextOffset,
    ) {
    }
}
