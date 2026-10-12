<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionNoForwardsRequest of MessageAction.
 */
final class MessageActionNoForwardsRequestData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'expired' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $expired,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $prevValue,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $newValue,
    ) {
    }
}
