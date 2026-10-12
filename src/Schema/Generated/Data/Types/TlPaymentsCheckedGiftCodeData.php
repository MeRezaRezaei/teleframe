<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.checkedGiftCode of payments.CheckedGiftCode.
 */
final class TlPaymentsCheckedGiftCodeData extends TlPaymentsCheckedGiftCodeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'viaGiveaway' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $viaGiveaway,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public ?int $giveawayMsgId,
    public ?int $toId,
    public int $date,
    public int $days,
    public ?int $usedDate,
    public array $chats,
    public array $users,
    ) {
    }
}
