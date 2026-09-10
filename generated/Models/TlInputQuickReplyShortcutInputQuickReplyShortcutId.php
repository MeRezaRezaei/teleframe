<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputQuickReplyShortcutId of InputQuickReplyShortcut (crc32 01190cf1). */
final class TlInputQuickReplyShortcutInputQuickReplyShortcutId extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_quick_reply_shortcut_input_quick_rep_7d036b24116c';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'shortcut_id' => 'int',
    ];
}
