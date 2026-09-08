<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for savedDialog of SavedDialog (crc32 bd87cb6c). */
final class TlSavedDialogSavedDialog extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_saved_dialog_saved_dialog';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'pinned' => 'bool',
        'peer' => 'string',
        'top_message' => 'int',
    ];
}
