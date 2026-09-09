<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswer;

/** Constructor model for messageActionPollAppendAnswer of MessageAction (crc32 9da1cd6c). */
final class TlMessageActionMessageActionPollAppendAnswer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_poll_append_answer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(TlPollAnswer::class, 'answer');
    }
}
