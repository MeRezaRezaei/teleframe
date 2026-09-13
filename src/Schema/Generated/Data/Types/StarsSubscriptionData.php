<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starsSubscription of StarsSubscription.
 */
final class StarsSubscriptionData extends TlStarsSubscriptionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'canceled' => ['flags', 0],
        'canRefulfill' => ['flags', 1],
        'missingBalance' => ['flags', 2],
        'botCanceled' => ['flags', 7],
    ];

    public function __construct(
    public int $flags,
    public ?bool $canceled,
    public ?bool $canRefulfill,
    public ?bool $missingBalance,
    public ?bool $botCanceled,
    public string $id,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $untilDate,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsSubscriptionPricingAbstractData $pricing,
    public ?string $chatInviteHash,
    public ?string $title,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebDocumentAbstractData $photo,
    public ?string $invoiceSlug,
    ) {
    }
}
