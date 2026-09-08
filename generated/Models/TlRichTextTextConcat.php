<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRichTextTextConcatTexts;

/** Constructor model for textConcat of RichText (crc32 7e6260d7). */
final class TlRichTextTextConcat extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_rich_text_text_concat';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function texts(): HasMany
    {
        return $this->tlChild(TlRichTextTextConcatTexts::class);
    }
}
