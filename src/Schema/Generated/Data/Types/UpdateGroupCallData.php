<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateGroupCall of Update.
 */
final class UpdateGroupCallData extends TlUpdateAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'liveStory' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $liveStory,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGroupCallAbstractData $call,
    ) {
    }
}
