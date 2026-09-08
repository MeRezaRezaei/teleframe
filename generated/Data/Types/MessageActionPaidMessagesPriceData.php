<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionPaidMessagesPrice of MessageAction.
 */
final class MessageActionPaidMessagesPriceData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'broadcastMessagesAllowed' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $broadcastMessagesAllowed,
    public int $stars,
    ) {
    }
}
