<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.searchCounter of messages.SearchCounter.
 */
final class TlMessagesSearchCounterData extends TlMessagesSearchCounterAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'inexact' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $inexact,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessagesFilterAbstractData $filter,
    public int $count,
    ) {
    }
}
