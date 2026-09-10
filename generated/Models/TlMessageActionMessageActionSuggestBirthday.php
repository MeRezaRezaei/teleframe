<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBirthday;

/** Constructor model for messageActionSuggestBirthday of MessageAction (crc32 2c8f2a25). */
final class TlMessageActionMessageActionSuggestBirthday extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_suggest_birthday';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function birthday(): BelongsTo
    {
        return $this->belongsTo(TlBirthday::class, 'birthday');
    }
}
