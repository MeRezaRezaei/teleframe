<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdate;

/** Constructor model for updateShort of Updates (crc32 78d4dec1). */
final class TlUpdatesUpdateShort extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_updates_update_short';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'int',
    ];

    public function updateAttr(): BelongsTo
    {
        return $this->belongsTo(TlUpdate::class, 'update');
    }
}
