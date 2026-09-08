<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessageDraftMessageEntities;

/** Constructor model for draftMessage of DraftMessage (crc32 60fe3294). */
final class TlDraftMessageDraftMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_draft_message_draft_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'no_webpage' => 'bool',
        'invert_media' => 'bool',
        'reply_to' => 'string',
        'message' => 'string',
        'media' => 'string',
        'date' => 'int',
        'effect' => 'int',
        'suggested_post' => 'string',
        'rich_message' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlDraftMessageDraftMessageEntities::class);
    }
}
