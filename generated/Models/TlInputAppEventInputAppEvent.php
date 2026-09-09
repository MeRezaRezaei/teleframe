<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlJSONValue;

/** Constructor model for inputAppEvent of InputAppEvent (crc32 1d1b1245). */
final class TlInputAppEventInputAppEvent extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_app_event_input_app_event';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'time' => 'float',
        'tl_type' => 'string',
        'peer' => 'int',
    ];

    public function data(): BelongsTo
    {
        return $this->belongsTo(TlJSONValue::class, 'data');
    }
}
