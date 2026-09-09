<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for textCustomEmoji of RichText (crc32 a26156c0). */
final class TlRichTextTextCustomEmoji extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_rich_text_text_custom_emoji';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'document_id' => 'int',
        'alt' => 'string',
    ];
}
