<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageViewsMessageViews;

/** Anchor model for TL type MessageReplies (spec §4.1). */
final class TlMessageReplies extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_replies_message_replies';

    protected $guarded = [];

    public function replies(): HasMany
    {
        return $this->hasMany(TlMessageMessage::class, 'replies');
    }
    public function repliesMessageViews(): HasMany
    {
        return $this->hasMany(TlMessageViewsMessageViews::class, 'replies');
    }
}
