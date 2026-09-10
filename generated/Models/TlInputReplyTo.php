<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessageDraftMessage;

/** Anchor model for TL type InputReplyTo (spec §4.1). */
final class TlInputReplyTo extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_reply_to_input_reply_to_message';

    protected $guarded = [];

    public function replyTo(): HasMany
    {
        return $this->hasMany(TlDraftMessageDraftMessage::class, 'reply_to');
    }
}
