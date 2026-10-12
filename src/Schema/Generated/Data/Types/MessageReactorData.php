<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageReactor of MessageReactor.
 */
final class MessageReactorData extends TlMessageReactorAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'top' => ['flags', 0],
        'my' => ['flags', 1],
        'anonymous' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $top,
    public ?bool $my,
    public ?bool $anonymous,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peerId,
    public int $count,
    ) {
    }
}
