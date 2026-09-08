<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stats.megagroupStats of stats.MegagroupStats.
 */
final class TlStatsMegagroupStatsData extends TlStatsMegagroupStatsAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsDateRangeDaysAbstractData $period,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $members,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $messages,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $viewers,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $posters,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $growthGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $membersGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $newMembersBySourceGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $languagesGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $messagesGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $actionsGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $topHoursGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $weekdaysGraph,
    public array $topPosters,
    public array $topAdmins,
    public array $topInviters,
    public array $users,
    ) {
    }
}
