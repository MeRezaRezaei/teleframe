<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for autoSaveException of AutoSaveException (crc32 81602d47). */
final class TlAutoSaveExceptionAutoSaveException extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_auto_save_exception_auto_save_exception';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'settings' => 'string',
    ];
}
