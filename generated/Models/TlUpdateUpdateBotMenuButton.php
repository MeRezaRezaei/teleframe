<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotMenuButton;

/** Constructor model for updateBotMenuButton of Update (crc32 14b85813). */
final class TlUpdateUpdateBotMenuButton extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_bot_menu_button';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'bot_id' => 'int',
    ];

    public function button(): BelongsTo
    {
        return $this->belongsTo(TlBotMenuButton::class, 'button');
    }
}
