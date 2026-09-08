<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputQuickReplyShortcut of InputQuickReplyShortcut (crc32 24596d41). */
final class TlInputQuickReplyShortcutInputQuickReplyShortcut extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_quick_reply_shortcut_input_quick_reply_shortcut';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'shortcut' => 'string',
    ];
}
