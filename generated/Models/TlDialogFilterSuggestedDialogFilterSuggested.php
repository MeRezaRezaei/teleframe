<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for dialogFilterSuggested of DialogFilterSuggested (crc32 77744d4a). */
final class TlDialogFilterSuggestedDialogFilterSuggested extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_dialog_filter_suggested_dialog_filter_suggested';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'filter' => 'string',
        'description' => 'string',
    ];
}
