<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputChatlistDialogFilter of InputChatlist (crc32 f3e0da33). */
final class TlInputChatlistInputChatlistDialogFilter extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_chatlist_input_chatlist_dialog_filter';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'filter_id' => 'int',
    ];
}
