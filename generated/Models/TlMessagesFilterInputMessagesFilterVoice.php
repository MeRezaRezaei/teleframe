<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputMessagesFilterVoice of MessagesFilter (crc32 50f5c392). */
final class TlMessagesFilterInputMessagesFilterVoice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_filter_input_messages_filter_voice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
