<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPrivacyKey;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePrivacyRules;

/** Constructor model for updatePrivacy of Update (crc32 ee3b272a). */
final class TlUpdateUpdatePrivacy extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_privacy';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function rules(): HasMany
    {
        return $this->tlChild(TlUpdateUpdatePrivacyRules::class);
    }

    public function key(): BelongsTo
    {
        return $this->belongsTo(TlPrivacyKey::class, 'tl_key');
    }
}
