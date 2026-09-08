<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for help.support of help.Support (crc32 17c6b5f6). */
final class TlHelpSupportSupport extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_help_support_support';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'phone_number' => 'string',
        'tl_user' => 'string',
    ];
}
