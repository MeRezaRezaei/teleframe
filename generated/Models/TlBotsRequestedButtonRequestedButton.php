<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for bots.requestedButton of bots.RequestedButton (crc32 f13bbcd7). */
final class TlBotsRequestedButtonRequestedButton extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bots_requested_button_requested_button';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'webapp_req_id' => 'string',
    ];
}
