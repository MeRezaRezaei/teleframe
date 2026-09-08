<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inlineBotSwitchPM of InlineBotSwitchPM (crc32 3c20629f). */
final class TlInlineBotSwitchPMInlineBotSwitchPM extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_inline_bot_switch_p_m_inline_bot_switch_p_m';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'text' => 'string',
        'start_param' => 'string',
    ];
}
