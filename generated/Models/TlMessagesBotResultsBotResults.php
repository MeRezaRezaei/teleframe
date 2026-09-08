<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesBotResultsBotResultsResults;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesBotResultsBotResultsUsers;

/** Constructor model for messages.botResults of messages.BotResults (crc32 e021f2f6). */
final class TlMessagesBotResultsBotResults extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_bot_results_bot_results';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'gallery' => 'bool',
        'query_id' => 'int',
        'next_offset' => 'string',
        'switch_pm' => 'string',
        'switch_webview' => 'string',
        'cache_time' => 'int',
    ];

    public function results(): HasMany
    {
        return $this->tlChild(TlMessagesBotResultsBotResultsResults::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesBotResultsBotResultsUsers::class);
    }
}
