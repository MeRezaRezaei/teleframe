<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageEntityFormattedDate of MessageEntity.
 */
final class MessageEntityFormattedDateData extends TlMessageEntityAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'relative' => ['flags', 0],
        'shortTime' => ['flags', 1],
        'longTime' => ['flags', 2],
        'shortDate' => ['flags', 3],
        'longDate' => ['flags', 4],
        'dayOfWeek' => ['flags', 5],
    ];

    public function __construct(
    public int $flags,
    public ?bool $relative,
    public ?bool $shortTime,
    public ?bool $longTime,
    public ?bool $shortDate,
    public ?bool $longDate,
    public ?bool $dayOfWeek,
    public int $offset,
    public int $length,
    public int $date,
    ) {
    }
}
