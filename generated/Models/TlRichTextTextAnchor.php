<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichText;

/** Constructor model for textAnchor of RichText (crc32 35553762). */
final class TlRichTextTextAnchor extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_rich_text_text_anchor';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'name' => 'string',
    ];

    public function text(): BelongsTo
    {
        return $this->belongsTo(TlRichText::class, 'text');
    }
}
