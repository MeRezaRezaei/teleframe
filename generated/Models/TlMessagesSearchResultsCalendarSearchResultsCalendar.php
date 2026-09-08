<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSearchResultsCalendarSearchReB1534ec0e56fPeriods;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSearchResultsCalendarSearchReB1534ec0e56fMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSearchResultsCalendarSearchReB1534ec0e56fChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSearchResultsCalendarSearchReB1534ec0e56fUsers;

/** Constructor model for messages.searchResultsCalendar of messages.SearchResultsCalendar (crc32 147ee23c). */
final class TlMessagesSearchResultsCalendarSearchResultsCalendar extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_search_results_calendar_search_re_b1534ec0e56f';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'inexact' => 'bool',
        'count' => 'int',
        'min_date' => 'int',
        'min_msg_id' => 'int',
        'offset_id_offset' => 'int',
    ];

    public function periods(): HasMany
    {
        return $this->tlChild(TlMessagesSearchResultsCalendarSearchReB1534ec0e56fPeriods::class);
    }
    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesSearchResultsCalendarSearchReB1534ec0e56fMessages::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesSearchResultsCalendarSearchReB1534ec0e56fChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesSearchResultsCalendarSearchReB1534ec0e56fUsers::class);
    }
}
