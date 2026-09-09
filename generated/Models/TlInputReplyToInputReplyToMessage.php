<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputReplyToInputReplyToMessageQuote_entities;

/** Constructor model for inputReplyToMessage of InputReplyTo (crc32 3bd4b7c2). */
final class TlInputReplyToInputReplyToMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_input_reply_to_input_reply_to_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'reply_to_msg_id' => 'int',
        'top_msg_id' => 'int',
        'quote_text' => 'string',
        'quote_offset' => 'int',
        'todo_item_id' => 'int',
        'poll_option' => 'string',
    ];

    public function quoteEntities(): HasMany
    {
        return $this->tlChild(TlInputReplyToInputReplyToMessageQuote_entities::class);
    }
}
