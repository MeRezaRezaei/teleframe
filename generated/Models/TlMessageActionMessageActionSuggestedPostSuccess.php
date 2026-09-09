<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsAmount;

/** Constructor model for messageActionSuggestedPostSuccess of MessageAction (crc32 95ddcf69). */
final class TlMessageActionMessageActionSuggestedPostSuccess extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_suggested_post_success';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function price(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'price');
    }
}
