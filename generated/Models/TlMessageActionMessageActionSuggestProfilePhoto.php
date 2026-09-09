<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;

/** Constructor model for messageActionSuggestProfilePhoto of MessageAction (crc32 57de635e). */
final class TlMessageActionMessageActionSuggestProfilePhoto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_suggest_profile_photo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'photo');
    }
}
