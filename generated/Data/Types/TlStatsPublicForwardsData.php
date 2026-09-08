<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stats.publicForwards of stats.PublicForwards.
 */
final class TlStatsPublicForwardsData extends TlStatsPublicForwardsAbstractData
{
    public function __construct(
    public int $flags,
    public int $count,
    public array $forwards,
    public ?string $nextOffset,
    public array $chats,
    public array $users,
    ) {
    }
}
