<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessageDraftMessageEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputReplyTo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSuggestedPost;

/** Constructor model for draftMessage of DraftMessage (crc32 60fe3294). */
final class TlDraftMessageDraftMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_draft_message_draft_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'no_webpage' => 'bool',
        'invert_media' => 'bool',
        'message' => 'string',
        'date' => 'int',
        'effect' => 'int',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlDraftMessageDraftMessageEntities::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(TlInputReplyTo::class, 'reply_to');
    }
    public function media(): BelongsTo
    {
        return $this->belongsTo(TlInputMedia::class, 'media');
    }
    public function suggestedPost(): BelongsTo
    {
        return $this->belongsTo(TlSuggestedPost::class, 'suggested_post');
    }
    public function richMessage(): BelongsTo
    {
        return $this->belongsTo(TlRichMessage::class, 'rich_message');
    }
}
