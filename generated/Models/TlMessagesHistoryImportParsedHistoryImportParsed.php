<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messages.historyImportParsed of messages.HistoryImportParsed (crc32 5e0fb7b9). */
final class TlMessagesHistoryImportParsedHistoryImportParsed extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_history_import_parsed_history_import_parsed';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'pm' => 'bool',
        'tl_group' => 'bool',
        'title' => 'string',
    ];
}
