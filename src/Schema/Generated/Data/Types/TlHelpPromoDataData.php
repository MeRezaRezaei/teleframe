<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.promoData of help.PromoData.
 */
final class TlHelpPromoDataData extends TlHelpPromoDataAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'proxy' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $proxy,
    public int $expires,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public ?string $psaType,
    public ?string $psaMessage,
    public array $pendingSuggestions,
    public array $dismissedSuggestions,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPendingSuggestionAbstractData $customPendingSuggestion,
    public array $chats,
    public array $users,
    ) {
    }
}
