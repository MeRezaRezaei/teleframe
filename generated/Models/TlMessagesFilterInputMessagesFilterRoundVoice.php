<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputMessagesFilterRoundVoice of MessagesFilter (crc32 7a7c17a4). */
final class TlMessagesFilterInputMessagesFilterRoundVoice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_filter_input_messages_filter_round_voice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
