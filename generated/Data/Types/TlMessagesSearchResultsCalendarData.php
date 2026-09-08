<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.searchResultsCalendar of messages.SearchResultsCalendar.
 */
final class TlMessagesSearchResultsCalendarData extends TlMessagesSearchResultsCalendarAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'inexact' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $inexact,
    public int $count,
    public int $minDate,
    public int $minMsgId,
    public ?int $offsetIdOffset,
    public array $periods,
    public array $messages,
    public array $chats,
    public array $users,
    ) {
    }
}
