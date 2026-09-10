<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDialogFilterOrderOrder;

/** Constructor model for updateDialogFilterOrder of Update (crc32 a5d72105). */
final class TlUpdateUpdateDialogFilterOrder extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_dialog_filter_order';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function order(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateDialogFilterOrderOrder::class);
    }
}
