<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputMessagesFilterRoundVideo of MessagesFilter (crc32 b549da53). */
final class TlMessagesFilterInputMessagesFilterRoundVideo extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_filter_input_messages_filter_round_video';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
