<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.peerColorOption of help.PeerColorOption.
 */
final class TlHelpPeerColorOptionData extends TlHelpPeerColorOptionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hidden' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hidden,
    public int $colorId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlHelpPeerColorSetAbstractData $colors,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlHelpPeerColorSetAbstractData $darkColors,
    public ?int $channelMinLevel,
    public ?int $groupMinLevel,
    ) {
    }
}
