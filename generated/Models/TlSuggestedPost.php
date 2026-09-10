<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessageDraftMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;

/** Anchor model for TL type SuggestedPost (spec §4.1). */
final class TlSuggestedPost extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_suggested_post_suggested_post';

    protected $guarded = [];

    public function suggestedPost(): HasMany
    {
        return $this->hasMany(TlMessageMessage::class, 'suggested_post');
    }
    public function suggestedPostDraftMessage(): HasMany
    {
        return $this->hasMany(TlDraftMessageDraftMessage::class, 'suggested_post');
    }
}
