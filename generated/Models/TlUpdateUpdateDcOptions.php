<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDcOptionsDc_options;

/** Constructor model for updateDcOptions of Update (crc32 8e5e9873). */
final class TlUpdateUpdateDcOptions extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_update_update_dc_options';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function dcOptions(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateDcOptionsDc_options::class);
    }
}
