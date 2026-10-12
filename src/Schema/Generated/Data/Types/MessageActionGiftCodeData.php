<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionGiftCode of MessageAction.
 */
final class MessageActionGiftCodeData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'viaGiveaway' => ['flags', 0],
        'unclaimed' => ['flags', 5],
    ];

    public function __construct(
    public int $flags,
    public ?bool $viaGiveaway,
    public ?bool $unclaimed,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $boostPeer,
    public int $days,
    public string $slug,
    public ?string $currency,
    public ?int $amount,
    public ?string $cryptoCurrency,
    public ?int $cryptoAmount,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $message,
    ) {
    }
}
