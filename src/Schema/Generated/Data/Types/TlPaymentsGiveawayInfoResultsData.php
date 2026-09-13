<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.giveawayInfoResults of payments.GiveawayInfo.
 */
final class TlPaymentsGiveawayInfoResultsData extends TlPaymentsGiveawayInfoAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'winner' => ['flags', 0],
        'refunded' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $winner,
    public ?bool $refunded,
    public int $startDate,
    public ?string $giftCodeSlug,
    public ?int $starsPrize,
    public int $finishDate,
    public int $winnersCount,
    public ?int $activatedCount,
    ) {
    }
}
