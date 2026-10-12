<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputStorePaymentPremiumGiveaway of InputStorePaymentPurpose.
 */
final class InputStorePaymentPremiumGiveawayData extends TlInputStorePaymentPurposeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'onlyNewSubscribers' => ['flags', 0],
        'winnersAreVisible' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $onlyNewSubscribers,
    public ?bool $winnersAreVisible,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPeerAbstractData $boostPeer,
    public ?array $additionalPeers,
    public ?array $countriesIso2,
    public ?string $prizeDescription,
    public int $randomId,
    public int $untilDate,
    public string $currency,
    public int $amount,
    ) {
    }
}
