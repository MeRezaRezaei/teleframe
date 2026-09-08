<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for boost of Boost.
 */
final class BoostData extends TlBoostAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'gift' => ['flags', 1],
        'giveaway' => ['flags', 2],
        'unclaimed' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $gift,
    public ?bool $giveaway,
    public ?bool $unclaimed,
    public string $id,
    public ?int $userId,
    public ?int $giveawayMsgId,
    public int $date,
    public int $expires,
    public ?string $usedGiftSlug,
    public ?int $multiplier,
    public ?int $stars,
    ) {
    }
}
