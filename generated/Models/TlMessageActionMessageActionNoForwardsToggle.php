<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;

/** Constructor model for messageActionNoForwardsToggle of MessageAction (crc32 bf7d6572). */
final class TlMessageActionMessageActionNoForwardsToggle extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_no_forwards_toggle';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function prevValue(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'prev_value');
    }
    public function newValue(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'new_value');
    }
}
