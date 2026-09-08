<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageMediaGiveawayResults of MessageMedia.
 */
final class MessageMediaGiveawayResultsData extends TlMessageMediaAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'onlyNewSubscribers' => ['flags', 0],
        'refunded' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $onlyNewSubscribers,
    public ?bool $refunded,
    public int $channelId,
    public ?int $additionalPeersCount,
    public int $launchMsgId,
    public int $winnersCount,
    public int $unclaimedCount,
    public array $winners,
    public ?int $months,
    public ?int $stars,
    public ?string $prizeDescription,
    public int $untilDate,
    ) {
    }
}
