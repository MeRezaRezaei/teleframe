<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.giveawayInfo of payments.GiveawayInfo.
 */
final class TlPaymentsGiveawayInfoData extends TlPaymentsGiveawayInfoAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'participating' => ['flags', 0],
        'preparingResults' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $participating,
    public ?bool $preparingResults,
    public int $startDate,
    public ?int $joinedTooEarlyDate,
    public ?int $adminDisallowedChatId,
    public ?string $disallowedCountry,
    ) {
    }
}
