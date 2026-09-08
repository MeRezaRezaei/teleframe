<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for textDate of RichText (crc32 a5b45e2b). */
final class TlRichTextTextDate extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_rich_text_text_date';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'relative' => 'bool',
        'short_time' => 'bool',
        'long_time' => 'bool',
        'short_date' => 'bool',
        'long_date' => 'bool',
        'day_of_week' => 'bool',
        'text' => 'string',
        'date' => 'int',
    ];
}
