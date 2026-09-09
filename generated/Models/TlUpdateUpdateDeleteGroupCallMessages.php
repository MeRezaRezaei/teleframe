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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDeleteGroupCallMessagesMessages;

/** Constructor model for updateDeleteGroupCallMessages of Update (crc32 3e85e92c). */
final class TlUpdateUpdateDeleteGroupCallMessages extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_delete_group_call_messages';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateDeleteGroupCallMessagesMessages::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(TlInputGroupCall::class, 'call');
    }
}
