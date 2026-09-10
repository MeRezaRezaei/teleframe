<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaidReactionPrivacy;

/** Constructor model for updatePaidReactionPrivacy of Update (crc32 8b725fce). */
final class TlUpdateUpdatePaidReactionPrivacy extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_paid_reaction_privacy';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function private(): BelongsTo
    {
        return $this->belongsTo(TlPaidReactionPrivacy::class, 'private');
    }
}
